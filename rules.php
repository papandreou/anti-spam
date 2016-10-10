<?php

$whitelistRules = [
    ['contains' => '/sent via our-little-app/i'],
    ['from' => '/@flygenring\.net/i'],
    ['from' => '/@facebookmail\.com/i'],
    ['from' => '/@em\.blizzard\.com/i'],
];

$rules = [
    
        // From: 
    ['from' => '@explorerec\.com', 'points' => 100],
    ['from' => '@netvision\.net', 'points' => 100],
    ['from' => '@shitmail\.me', 'points' => 100],
    ['from' => 'eachbuyer\.net', 'points' => 100],
    
    /* ['contains' => 'finding IT opportunities', 'points' => 100],
    ['contains' => 'PHP specialists?', 'points' => 80],
    ['contains' => 'startups?', 'points' => 10],
    ['contains' => 'saw your profile on GitHub', 'points' => 50],
    ['contains' => 'explore-group\.com', 'points' => 100],
    
    ['contains' => 'urgent(ly)? need', 'points' => 30],
    ['contains' => 'full-stack developer', 'points' => 30],
    ['contains' => 'interviews?', 'points' => 20],
    ['contains' => 'skills', 'points' => 10],
    ['contains' => 'candidates?', 'points' => 20], */
    
    // My rules:
        // Words:
    ['contains' => 'company|firma?', 'points' => 10],
    ['contains' => '\$', 'points' => 10],
    
    ['contains' => '(?:\$|eur|euro|dollars?|usd|dkk).\d+(?:[,.]\d+)?', 'points' => 20], // Matches: $50.000 | $50000 | usd 50.000
    ['contains' => '\d+(?:[,.]\d+)?(?:.*(?:million|trillion|billion)?(?:eur|dkk|dollars?|usd))', 'points' => 20], // Matches: 50000 dollar | 50.000 dollar | 680 Trillion dollars
    ['contains' => 'incredible', 'points' => 20],
    
    ['contains' => 'part-time', 'points' => 30],
    ['contains' => 'employment', 'points' => 30],
    ['contains' => 'kærlighed(en)', 'points' => 30],
    ['contains' => 'CV', 'points' => 30],
    ['contains' => 'free|gratis', 'points' => 30],
    ['contains' => 'premium', 'points' => 30],
    ['contains' => 'scoret?', 'points' => 30],
    ['contains' => 'urgent(ly)?', 'points' => 30],
    
    ['contains' => 'liderlig', 'points' => 40],
    ['contains' => 'knald', 'points' => 40],
    ['contains' => 'horny', 'points' => 40],
    ['contains' => 'beneficial', 'points' => 40],
    ['contains' => 'offer', 'points' => 40],
    ['contains' => 'proposition', 'points' => 40],
    ['contains' => 'salary', 'points' => 40],
    ['contains' => 'income', 'points' => 40],
    ['contains' => 'hand\ ?bags?', 'points' => 40], // handsbags, handbag, hand bags, hand bag
    
    ['contains' => 'revenues?', 'points' => 50],
    ['contains' => 'vacanc(ies|y)', 'points' => 50],
    ['contains' => 'chatte', 'points' => 50],
    ['contains' => 'lottery', 'points' => 50],
    ['contains' => 'award', 'points' => 50],
    ['contains' => 'winning', 'points' => 50],
    ['contains' => 'won', 'points' => 50],
    
    ['contains' => 'hot(tie)?', 'points' => 60],
    ['contains' => 'sex', 'points' => 60],
    ['contains' => 'profits?', 'points' => 60],
        
        // Phrases:
    ['contains' => 'new position', 'points' => 20],
    ['contains' => 'huge plus', 'points' => 20],
    
    ['contains' => 'venter pa dig', 'points' => 30],
    
    ['contains' => 'this is why', 'points' => 40],
    
    ['contains' => 'We offer', 'points' => 50],
    ['contains' => '(per|\/).?(?:day|week|month|year)', 'points' => 50],
    ['contains' => 'personal invitation', 'points' => 50],
    ['contains' => 'you need', 'points' => 50],
    
    ['contains' => 'cooperation with', 'points' => 60],
    ['contains' => 'Flexible schedule', 'points' => 60],
    ['contains' => 'stort internationalt firma', 'points' => 60],
    ['contains' => '100% free', 'points' => 60],
    ['contains' => 'it actually works', 'points' => 60],
    ['contains' => '% Off( today)?', 'points' => 60],
    
    ['contains' => 'Hi friend', 'points' => 70],
    ['contains' => 'Lønnen er på', 'points' => 70],
    
    ['contains' => 'maske hjælpe', 'points' => 80],
    ['contains' => 'naeste date', 'points' => 80],
    ['contains' => 'Work with us', 'points' => 80],
    ['contains' => 'jagt efter', 'points' => 80],
    ['contains' => 'Alle far sig', 'points' => 80],

        // Sentences:
    ['contains' => 'Skal vi ikke modes', 'points' => 90],
    ['contains' => 'nogen billeder af kendte', 'points' => 90],
    ['contains' => 'mode den eneste ene', 'points' => 90],

];