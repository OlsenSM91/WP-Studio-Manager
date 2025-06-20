<?php
class WSM_Education_Config extends WSM_Industry_Config {
    protected $labels = [
        'participant' => 'Student',
        'session' => 'Lesson',
        'instructor' => 'Teacher'
    ];
    protected $features = ['progress_tracking', 'assignments', 'recitals'];
    protected $required_fields = ['skill_level', 'instrument', 'parent_contact'];
    protected $integrations = ['practice_tracking', 'sheet_music'];
}
