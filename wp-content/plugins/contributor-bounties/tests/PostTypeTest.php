<?php
class Contributor_Bounties_PostType_Test extends WP_UnitTestCase {
    public function test_post_type_registered() {
        do_action('init');
        $post_type = get_post_type_object('bounty_campaign');
        $this->assertNotNull($post_type);
        $this->assertSame('bounty_campaign', $post_type->name);
    }
}
