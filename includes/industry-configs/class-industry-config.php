<?php
abstract class WSM_Industry_Config implements WSM_Industry_Config_Interface {
    protected $labels = [];
    protected $features = [];
    protected $required_fields = [];
    protected $integrations = [];

    public function get_labels() {
        return $this->labels;
    }

    public function get_features() {
        return $this->features;
    }

    public function get_required_fields() {
        return $this->required_fields;
    }

    public function get_integrations() {
        return $this->integrations;
    }
}
