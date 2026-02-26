<?php
class DDNS_Node_Telemetry_Test extends WP_UnitTestCase {
    public function test_cron_schedule_added() {
        $schedules = apply_filters('cron_schedules', array());
        $this->assertArrayHasKey('ddns_node_3min', $schedules);
        $this->assertSame(180, $schedules['ddns_node_3min']['interval']);
    }
}
