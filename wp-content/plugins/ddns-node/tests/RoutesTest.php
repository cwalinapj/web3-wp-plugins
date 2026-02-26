<?php
class DDNS_Node_Routes_Test extends WP_UnitTestCase {
    public function test_rest_routes_registered() {
        do_action('rest_api_init');
        $server = rest_get_server();
        $routes = $server->get_routes();
        $this->assertArrayHasKey('/ddns/v1/resolve', $routes);
        $this->assertArrayHasKey('/ddns/v1/health', $routes);
    }
}
