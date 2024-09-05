<?php

if ( ! defined( 'ABSPATH' ) ) exit;

class DS_Events_Component extends BP_Component {

    function __construct() {
        global $bp;

        parent::start( 'events', __( 'Events', 'digitalSquadrons'), )
    }
}