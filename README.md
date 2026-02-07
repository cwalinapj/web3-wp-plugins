# web3-wp-plugins

1) Plugin A: “Contributor Bounties” for blog posts

Purpose: attract writers to publish posts about a subject by putting money/credits behind it.

How it works
	•	Admin creates a Bounty Campaign:
	•	topic/keywords
	•	content requirements (word count, sources, originality rules)
	•	payout (fixed or tiered)
	•	max winners / deadline
	•	Contributors submit drafts via:
	•	WP “pending post” submission form (front-end) or
	•	GitHub PR submission (optional, best for quality control)
	•	Plugin runs quality gates:
	•	plagiarism check (hash + optional AI classifier)
	•	formatting/SEO checklist
	•	required links/sections present
	•	Admin approves → payout released

What to have agent build (MVP tasks)
	1.	plugins/wp-contributor-bounties/
	2.	CPTs:
	•	ddns_campaign
	•	ddns_submission
	3.	Front-end submission form (logged-in or magic-link)
	4.	Admin review queue (approve/reject, feedback)
	5.	Payout trigger hook (calls control-plane API, not keys in WP)

⸻

2) Plugin B: “Fix Marketplace” with stake + escrow

Purpose: user stakes an amount into a marketplace to get help fixing their WP issue. Helpers compete/accept the job.

Best model (so it’s not scammy)

Use an escrow contract and pay only when:
	•	the fix is delivered (in staging first)
	•	verification passes (tests + screenshots)
	•	user accepts (or arbitration rules)

Flow
	1.	User opens a “Fix Request” in WP admin:
	•	describe issue
	•	upload logs
	•	select urgency
	•	choose stake amount
	2.	Stake is deposited to escrow with job_id.
	3.	Market participants (helpers) submit:
	•	bid (amount/time)
	•	proof of expertise (reputation)
	4.	User selects a helper (or auto-match).
	5.	Helper works in staging (your runner system), proposes patch/steps.
	6.	Verification run:
	•	health checks
	•	error logs clean
	•	vision diff within threshold
	7.	User approves:
	•	escrow pays helper
	•	optional small fee to protocol treasury
	8.	If no resolution by deadline:
	•	stake refunded or partial penalty depending on rules

What to have agent build (MVP tasks)
	1.	plugins/wp-fix-market/
	2.	WP admin UI:
	•	“Create Fix Request”
	•	“View status”
	•	“Accept solution”
	3.	Control-plane endpoints:
	•	POST /v1/market/jobs/create
	•	GET /v1/market/jobs/:id
	•	POST /v1/market/jobs/:id/accept-helper
	•	POST /v1/market/jobs/:id/submit-solution
	•	POST /v1/market/jobs/:id/approve
	4.	Escrow contract (EVM L2) with:
	•	deposit(jobId)
	•	release(jobId, helper)
	•	refund(jobId)
	•	(optional) dispute(jobId) later
	5.	Runner integration:
	•	“solution” is applied in staging
	•	runner verifies and emits a signed “pass/fail” attestation to control-plane

⸻

3) Marketplace service (not inside WP)

Don’t build the “market” directly in WP. Build it as a service that WP talks to.

Add to repo:
	•	services/control-plane/routes/market/*
	•	contracts/escrow-market/*

This lets you later:
	•	support multiple sites
	•	add reputation and anti-fraud
	•	integrate “free if hosting miner” credits
