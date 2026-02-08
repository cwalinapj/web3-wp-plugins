<?php
class DDNS_AI_Admin_PluginLoad_Test extends WP_UnitTestCase {
    public function test_placeholder_function() {
        $this->assertTrue(function_exists('ddns_ai_admin_placeholder'));
        $this->assertSame('DDNS AI admin placeholder', ddns_ai_admin_placeholder());
    }
}
