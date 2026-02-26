/* global WEB3WAL */
(function () {
  const isLoginPage = !!(WEB3WAL && WEB3WAL.isLoginPage);
  const wcProjectId = WEB3WAL && WEB3WAL.wcProjectId;

  function setStatus(id, msg, isErr) {
    const el = document.getElementById(id);
    if (!el) return;
    el.textContent = msg;
    el.className = el.className.replace(/\berr\b/g, '').trim() + (isErr ? ' err' : '');
  }

  async function postForm(obj) {
    const body = new URLSearchParams(obj);
    const res = await fetch(WEB3WAL.ajaxUrl, {
      method: "POST",
      headers: { "Content-Type": "application/x-www-form-urlencoded; charset=UTF-8" },
      body,
      credentials: "same-origin",
    });
    return res.json();
  }

  async function getChallenge({ address, chain, chainId, context }) {
    const r = await postForm({
      action: "web3wal_get_challenge",
      wpNonce: WEB3WAL.wpNonce,
      address,
      chain,
      chainId: String(chainId || 0),
      context,
    });
    if (!r?.success) throw new Error(r?.data?.message || "Challenge failed");
    return r.data; // { message, challenge }
  }

  async function verify({ address, chain, chainId, nonce, signature, context, pageUrl }) {
    const r = await postForm({
      action: "web3wal_verify",
      wpNonce: WEB3WAL.wpNonce,
      address,
      chain,
      chainId: String(chainId || 0),
      nonce,
      signature,
      context,
      pageUrl: pageUrl || "",
    });
    if (!r?.success) throw new Error(r?.data?.message || "Verification failed");
    return r.data; // { redirect, display }
  }

  // -------- EVM: injected (MetaMask / etc.) --------
  async function loginEvmInjected({ context, statusElId, pageUrl }) {
    if (!window.ethereum) throw new Error("No EVM wallet found. Please install MetaMask or a compatible wallet.");
    setStatus(statusElId, "Connecting wallet…");

    const accounts = await window.ethereum.request({ method: "eth_requestAccounts" });
    const address = (accounts?.[0] || "").toLowerCase();
    if (!address) throw new Error("No account selected.");

    const chainIdHex = await window.ethereum.request({ method: "eth_chainId" });
    const chainId = parseInt(chainIdHex, 16);

    setStatus(statusElId, "Requesting challenge…");
    const { message, challenge } = await getChallenge({ address, chain: "evm", chainId, context });

    setStatus(statusElId, "Signing message…");
    const signature = await window.ethereum.request({
      method: "personal_sign",
      params: [message, address],
    });

    setStatus(statusElId, "Verifying…");
    const out = await verify({ address, chain: "evm", chainId, nonce: challenge.nonce, signature, context, pageUrl });

    setStatus(statusElId, "Success! Redirecting…");
    window.location.href = out.redirect || (context === "wp_login" ? "/wp-admin/" : "/");
  }

  // -------- EVM: WalletConnect (QR + many hardware wallet flows via companion apps) --------
  async function loginWalletConnect({ context, statusElId, pageUrl }) {
    if (!wcProjectId) throw new Error("WalletConnect Project ID not configured (Settings → General).");
    if (!window.WalletConnectEthereumProvider) throw new Error("WalletConnect provider library not loaded.");

    setStatus(statusElId, "Opening WalletConnect… Scan QR with your wallet.");

    // v2 init
    const provider = await window.WalletConnectEthereumProvider.init({
      projectId: wcProjectId,
      showQrModal: true,

      // Some wallets prefer at least one chain; allow switching. Read chainId after connect.
      chains: [1],
      optionalChains: [1, 10, 137, 42161, 8453, 56, 43114],
    });

    await provider.connect();

    const accounts = await provider.request({ method: "eth_accounts" });
    const address = (accounts?.[0] || "").toLowerCase();
    if (!address) throw new Error("No account connected.");

    const chainId = await provider.request({ method: "eth_chainId" });
    const chainIdNum = typeof chainId === "string" ? parseInt(chainId, 16) : Number(chainId);

    setStatus(statusElId, "Requesting challenge…");
    const { message, challenge } = await getChallenge({ address, chain: "evm", chainId: chainIdNum, context });

    setStatus(statusElId, "Signing message…");
    const signature = await provider.request({
      method: "personal_sign",
      params: [message, address],
    });

    setStatus(statusElId, "Verifying…");
    const out = await verify({ address, chain: "evm", chainId: chainIdNum, nonce: challenge.nonce, signature, context, pageUrl });

    setStatus(statusElId, "Success! Redirecting…");
    window.location.href = out.redirect || (context === "wp_login" ? "/wp-admin/" : "/");
  }

  // -------- Solana: Phantom --------
  async function loginSolana({ context, statusElId, pageUrl }) {
    const provider = window?.solana;
    if (!provider || !provider.isPhantom) throw new Error("Phantom not found. Please install Phantom wallet.");

    setStatus(statusElId, "Connecting Phantom…");
    const resp = await provider.connect();
    const pubkey = resp?.publicKey?.toString();
    if (!pubkey) throw new Error("No Solana account selected.");

    setStatus(statusElId, "Requesting challenge…");
    const { message, challenge } = await getChallenge({ address: pubkey, chain: "solana", chainId: 0, context });

    setStatus(statusElId, "Signing message…");
    const enc = new TextEncoder();
    const msgBytes = enc.encode(message);
    const signed = await provider.signMessage(msgBytes, "utf8");
    const sigB64 = btoa(String.fromCharCode(...signed.signature));

    setStatus(statusElId, "Verifying…");
    const out = await verify({ address: pubkey, chain: "solana", chainId: 0, nonce: challenge.nonce, signature: sigB64, context, pageUrl });

    setStatus(statusElId, "Success! Redirecting…");
    window.location.href = out.redirect || (context === "wp_login" ? "/wp-admin/" : "/");
  }

  // ---- Bind wp-login buttons ----
  function bindLoginPage() {
    const statusId = "web3wal-status";
    document.querySelectorAll('[data-web3wal]').forEach(btn => {
      btn.addEventListener('click', async () => {
        const type = btn.getAttribute('data-web3wal');
        try {
          if (type === 'metamask') return await loginEvmInjected({ context: 'wp_login', statusElId: statusId, pageUrl: window.location.href });
          if (type === 'walletconnect') return await loginWalletConnect({ context: 'wp_login', statusElId: statusId, pageUrl: window.location.href });
          if (type === 'phantom') return await loginSolana({ context: 'wp_login', statusElId: statusId, pageUrl: window.location.href });
        } catch (e) {
          setStatus(statusId, e.message || 'Error', true);
        }
      });
    });
  }

  // ---- Front-end floating button ----
  function bindFrontend() {
    const root = document.getElementById('web3wal-front');
    if (!root) return;

    const toggle = document.getElementById('web3wal-front-toggle');
    const menu = document.getElementById('web3wal-front-menu');
    const statusId = 'web3wal-front-status';

    if (toggle && menu) {
      toggle.addEventListener('click', () => {
        menu.style.display = (menu.style.display === 'none' || !menu.style.display) ? 'block' : 'none';
      });
    }

    root.querySelectorAll('[data-web3wal-front]').forEach(btn => {
      btn.addEventListener('click', async () => {
        const type = btn.getAttribute('data-web3wal-front');
        try {
          if (type === 'metamask') return await loginEvmInjected({ context: 'site_signin', statusElId: statusId, pageUrl: window.location.href });
          if (type === 'walletconnect') return await loginWalletConnect({ context: 'site_signin', statusElId: statusId, pageUrl: window.location.href });
          if (type === 'phantom') return await loginSolana({ context: 'site_signin', statusElId: statusId, pageUrl: window.location.href });
        } catch (e) {
          setStatus(statusId, e.message || 'Error', true);
        }
      });
    });
  }

  document.addEventListener("DOMContentLoaded", () => {
    if (isLoginPage) bindLoginPage();
    bindFrontend();
  });
})();
