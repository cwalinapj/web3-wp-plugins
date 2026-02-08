<?php
class DDNS_Compat_PluginLoad_Test extends WP_UnitTestCase {
    public function test_plugin_constants_defined() {
        $this->assertTrue(defined('DDNS_COMPAT_VERSION'));
        $this->assertTrue(defined('DDNS_COMPAT_PATH'));
        $this->assertTrue(defined('DDNS_COMPAT_URL'));
    }
}
