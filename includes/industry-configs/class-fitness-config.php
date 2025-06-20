<?php
class WSM_Fitness_Config extends WSM_Industry_Config {
    protected $labels = [
        'participant' => 'Member',
        'session' => 'Class',
        'instructor' => 'Trainer'
    ];
    protected $features = ['body_composition', 'progress_photos', 'workout_plans'];
    protected $required_fields = ['emergency_contact', 'health_conditions'];
    protected $integrations = ['heart_rate_monitors', 'nutrition_tracking'];
}
