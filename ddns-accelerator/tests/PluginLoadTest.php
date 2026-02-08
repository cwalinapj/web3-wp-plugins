<?php
class DDNS_Accelerator_PluginLoad_Test extends WP_UnitTestCase {
    public function test_plugin_constants_defined() {
        $this->assertTrue(defined('DDNS_ACCELERATOR_VERSION'));
        $this->assertTrue(defined('DDNS_ACCELERATOR_PATH'));
        $this->assertTrue(defined('DDNS_ACCELERATOR_URL'));
    }
}
