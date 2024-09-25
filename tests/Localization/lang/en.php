<?php

return [
    'root_key_1' => 'Main Root 1',
    'root_key_2' => 'Main Root 2',
    'root_key_3' => 'Main Root 3',
    'test_replace' => 'Hello, :name! Welcome to :framework!',
    'test_plural' => ':count {:count|book|books}',
    'test_plural2' => ':count { :count | book | books }',
    'test_choice' => '{:num | [0] zero | [1] one | [2] two | [3,5] from three to five | [6,*] other }',
    'test_choice_multi' => '{:num | 
            [0] zero | 
            [1] one | 
            [2] two | 
            [3,5] from three to five | 
            [6,*] other 
        }',
    'test_replace_case' => 'Upper: :upper:value1, Lower: :lower:value2, Ucfirst: :ucfirst:value3',
];