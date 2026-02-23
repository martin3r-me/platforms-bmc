<?php

/**
 * Osterwalder Business Model Canvas - Block Definitions & Guiding Questions
 */
return [
    'block_types' => [
        'customer_segments' => [
            'label' => 'Customer Segments',
            'description' => 'The different groups of people or organizations an enterprise aims to reach and serve.',
            'position' => 1,
            'guiding_questions' => [
                'For whom are we creating value?',
                'Who are our most important customers?',
                'What are the customer archetypes?',
                'Is this a mass market, niche market, segmented, diversified, or multi-sided platform?',
            ],
        ],
        'value_propositions' => [
            'label' => 'Value Propositions',
            'description' => 'The bundle of products and services that create value for a specific Customer Segment.',
            'position' => 2,
            'guiding_questions' => [
                'What value do we deliver to the customer?',
                'Which one of our customer problems are we helping to solve?',
                'What bundles of products and services are we offering to each Customer Segment?',
                'Which customer needs are we satisfying?',
            ],
        ],
        'channels' => [
            'label' => 'Channels',
            'description' => 'How a company communicates with and reaches its Customer Segments to deliver a Value Proposition.',
            'position' => 3,
            'guiding_questions' => [
                'Through which channels do our Customer Segments want to be reached?',
                'How are we reaching them now?',
                'How are our channels integrated?',
                'Which ones work best? Which ones are most cost-efficient?',
            ],
        ],
        'customer_relationships' => [
            'label' => 'Customer Relationships',
            'description' => 'The types of relationships a company establishes with specific Customer Segments.',
            'position' => 4,
            'guiding_questions' => [
                'What type of relationship does each of our Customer Segments expect us to establish and maintain?',
                'Which ones have we established?',
                'How are they integrated with the rest of our business model?',
                'How costly are they?',
            ],
        ],
        'revenue_streams' => [
            'label' => 'Revenue Streams',
            'description' => 'The cash a company generates from each Customer Segment.',
            'position' => 5,
            'guiding_questions' => [
                'For what value are our customers really willing to pay?',
                'For what do they currently pay?',
                'How are they currently paying?',
                'How would they prefer to pay?',
                'How much does each Revenue Stream contribute to overall revenues?',
            ],
        ],
        'key_resources' => [
            'label' => 'Key Resources',
            'description' => 'The most important assets required to make a business model work.',
            'position' => 6,
            'guiding_questions' => [
                'What Key Resources do our Value Propositions require?',
                'What resources do our Distribution Channels require?',
                'What resources do our Customer Relationships require?',
                'What resources do our Revenue Streams require?',
            ],
        ],
        'key_activities' => [
            'label' => 'Key Activities',
            'description' => 'The most important things a company must do to make its business model work.',
            'position' => 7,
            'guiding_questions' => [
                'What Key Activities do our Value Propositions require?',
                'What activities do our Distribution Channels require?',
                'What activities do our Customer Relationships require?',
                'What activities do our Revenue Streams require?',
            ],
        ],
        'key_partners' => [
            'label' => 'Key Partners',
            'description' => 'The network of suppliers and partners that make the business model work.',
            'position' => 8,
            'guiding_questions' => [
                'Who are our Key Partners?',
                'Who are our key suppliers?',
                'Which Key Resources are we acquiring from partners?',
                'Which Key Activities do partners perform?',
            ],
        ],
        'cost_structure' => [
            'label' => 'Cost Structure',
            'description' => 'All costs incurred to operate a business model.',
            'position' => 9,
            'guiding_questions' => [
                'What are the most important costs inherent in our business model?',
                'Which Key Resources are most expensive?',
                'Which Key Activities are most expensive?',
                'Is the business more cost-driven or value-driven?',
            ],
        ],
    ],
];
