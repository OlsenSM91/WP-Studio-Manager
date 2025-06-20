<?php
class WSM_Creative_Config extends WSM_Industry_Config {
    protected $labels = [
        'participant' => 'Artist',
        'session' => 'Workshop',
        'instructor' => 'Instructor'
    ];
    protected $features = ['portfolio', 'supply_lists', 'project_gallery'];
    protected $required_fields = ['experience_level', 'materials_owned'];
    protected $integrations = ['portfolio_sites', 'social_sharing'];
}
