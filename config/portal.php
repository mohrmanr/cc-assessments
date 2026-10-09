<?php

return [
    'pcl5_demo_items' => [
        ['id' => 'pcl_1', 'text' => 'Repeated, disturbing memories, thoughts, or images of the stressful experience?'],
        ['id' => 'pcl_2', 'text' => 'Repeated, disturbing dreams of the stressful experience?'],
        ['id' => 'pcl_3', 'text' => 'Suddenly acting or feeling as if the stressful experience were happening again?'],
        ['id' => 'pcl_4', 'text' => 'Feeling very upset when something reminded you of the stressful experience?'],
        ['id' => 'pcl_5', 'text' => 'Having strong physical reactions when something reminded you of the experience?'],
        ['id' => 'pcl_6', 'text' => 'Avoiding memories, thoughts, or feelings related to the stressful experience?'],
        ['id' => 'pcl_7', 'text' => 'Avoiding external reminders (people, places, conversations) of the stressful experience?'],
        ['id' => 'pcl_8', 'text' => 'Trouble remembering important parts of the stressful experience?'],
    ],

    'pcl5_response_labels' => [
        0 => 'Not at all',
        1 => 'A little bit',
        2 => 'Moderately',
        3 => 'Quite a bit',
        4 => 'Extremely',
    ],

    /*
     * Demo uses 8 PCL-5 items (max score 32). Threshold scaled from full PCL-5 cutoff of 33/80.
     */
    'pcl5_demo_threshold' => 13,

    'admin_notification_emails' => [
        'rucampbell@connectionscounseling.org',
        'rmohrmann@gmail.com',
    ],

    'baseline_surveys' => [
        'pcl-5' => [
            'route_name' => 'participant.assessments.pcl5',
            'store_route_name' => 'participant.assessments.pcl5.store',
            'title' => 'PCL-5 Baseline Assessment',
            'label' => 'PCL-5',
            'description' => 'Complete the PCL-5 to establish your baseline PTSD symptom score.',
            'instructions' => 'Over the last month, how much were you bothered by each item below?',
            'items_key' => 'pcl5_demo_items',
            'labels_key' => 'pcl5_response_labels',
            'min' => 0,
            'max' => 4,
            'default' => 3,
            'score_max' => 32,
        ],
        'ace' => [
            'route_name' => 'participant.assessments.ace',
            'store_route_name' => 'participant.assessments.ace.store',
            'title' => 'Adverse Childhood Experiences',
            'label' => 'ACE',
            'description' => 'Complete the Adverse Childhood Experiences assessment.',
            'intro' => [
                'Our relationships and experiences, even those in childhood, can affect our health and well-being. Difficult childhood experiences are very common. Please tell us whether you have had any of the experiences listed below, as they may be affecting your health today or may affect your health in the future.',
                'This information will help you and your provider better understand how to work together to support your health and well-being. Collating of information for research or assessment purposes will be done without sharing any identifying information.',
                'Your confidentiality is protected. Your identifying information will not be shared with anyone other than the therapist with whom you are working.',
            ],
            'fields' => [],
            'instructions' => 'Below is a list of 10 categories of Adverse Childhood Experiences (ACEs). From the list below, please place a checkmark next to each ACE category that you experienced prior to your 18th birthday. Yes = 1, No = 0.',
            'closing' => 'Thank you for answering these questions about your childhood experiences. Your therapist will be able to talk with you about the results.',
            'items_key' => 'ace_items',
            'labels_key' => 'ace_response_labels',
            'min' => 0,
            'max' => 1,
            'default' => null,
            'score_max' => 10,
        ],
    ],

    'ace_items' => [
        ['id' => 'ace_1', 'text' => 'Did you feel that you did not have enough to eat, had to wear dirty clothes, or had no one to protect or take care of you?'],
        ['id' => 'ace_2', 'text' => 'Did you lose a parent through divorce, abandonment, death, or other reason?'],
        ['id' => 'ace_3', 'text' => 'Did you live with anyone who was depressed, mentally ill, or attempted suicide?'],
        ['id' => 'ace_4', 'text' => 'Did you live with anyone who had a problem with drinking or using drugs, including prescription drugs?'],
        ['id' => 'ace_5', 'text' => 'Did your parents or adults in your home ever hit, punch, beat, or threaten to harm each other?'],
        ['id' => 'ace_6', 'text' => 'Did you live with anyone who went to jail or prison?'],
        ['id' => 'ace_7', 'text' => 'Did a parent or adult in your home ever swear at you, insult you, or put you down?'],
        ['id' => 'ace_8', 'text' => 'Did a parent or adult in your home ever hit, beat, kick, or physically hurt you in any way?'],
        ['id' => 'ace_9', 'text' => 'Did you feel that no one in your family loved you or thought you were special?'],
        ['id' => 'ace_10', 'text' => 'Did you experience unwanted sexual contact (such as fondling or oral/anal/vaginal intercourse/penetration)?'],
    ],

    'ace_response_labels' => [
        0 => 'No',
        1 => 'Yes',
    ],
];
