<?php
class DDNS_Optin_PluginLoad_Test extends WP_UnitTestCase {
    public function test_shortcode_registered() {
        $this->assertTrue(shortcode_exists('ddns_optin'));
    }
}
