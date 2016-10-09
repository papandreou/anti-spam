<?php

require_once '../vendor/autoload.php';
require_once '../accounts.php';

use Fetch\Message;
use Fetch\Server;

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

$whitelistRules = [
    ['contains' => '/sent via our-little-app/i'],
    ['from' => '/@flygenring\.net/i'],
    ['from' => '/@facebookmail\.com/i'],
    ['from' => '/@em\.blizzard\.com/i'],
];

$points = [];
foreach ($rules as $key => &$rule) {
    $points[$key] = $rule['points'];
    if (isset($rule['contains'])) {
        $rule['contains'] = '/' . $rule['contains'] . '/i';
    }
    if (isset($rule['from'])) {
        $rule['from'] = '/' . $rule['from'] . '/i';
    }
}
array_multisort($points, SORT_DESC, $rules);

$unreadMessages = [];
foreach ($inboxes as $id => $inbox) {
    $server = new Server($inbox['imap'], 993);
    $server->setAuthentication($inbox['username'], $inbox['password']);

    if (!$server->hasMailBox($inboxes[$id]['prefix'] . $inboxes[$id]['folder'])) {
        $server->createMailBox($inboxes[$id]['prefix'] . $inboxes[$id]['folder']);
    }

    $unreadMessages[$id] = $server->search('UNSEEN');
}

foreach ($unreadMessages as $id => $messages) {
    echo "<br>\n<b>Now processing:</b> " . $id . "<br>\n";
    file_put_contents("../logs/spam.log", "\nNow processing: " . $id . "\n", FILE_APPEND);
    if (!empty($messages)) {
        $mailer = Swift_Mailer::newInstance(
            Swift_SmtpTransport::newInstance(
                $inboxes[$id]['smtp'], $inboxes[$id]['smtp_port'],
                (isset($inboxes[$id]['starttls'])) ? 'tls' : null
            )
                ->setUsername($inboxes[$id]['username'])
                ->setPassword($inboxes[$id]['password'])
                ->setStreamOptions(
                    [
                        'ssl' => [
                            'allow_self_signed' => true,
                            'verify_peer' => false,
                        ],
                    ]
                )

        );
    } else {
        continue;
    }
    /**
     * @var Message $message
     */
    foreach ($messages as $i => $message) {

        //$spam = isRecruiterSpam($rules, $message) ? '' : 'not';
        $whitelisted = isWhitelisted($whitelistRules, $message) ? true : false;
        
        if ($whitelisted == true) {
            echo "<span style=\"background-color: #73ca73;\">Subject for {$i}: \"{$message->getSubject()}\" was found in <b>whitelist</b> rules.</span><br>\n";
            //file_put_contents($inboxes[$id]['logfile'], "Whitelist: {$i}: \"{$message->getSubject()}\"\n", FILE_APPEND);
            //echo $msg;
        }
        elseif ($whitelisted == false) {
            $spam = isRecruiterSpam($rules, $message) ? true : false;
            
            if ($whitelisted == false && $spam == true) {

                $message->setFlag(Message::FLAG_SEEN);

                $potentialSender = $message->getAddresses('to')[0]['address'];
                $sender = (in_array($potentialSender, $inboxes[$id]['aliases']))
                    ? $potentialSender : $inboxes[$id]['aliases'][0];

                $reply = Swift_Message::newInstance('Re: ' . $message->getSubject())
                    ->setFrom($message->getAddresses('to')[0]['address'])
                    ->setTo($message->getAddresses('from')['address'])
                    ->setBody(
                        file_get_contents('../templates/recruiter.html'),
                        'text/html'
                    );

                //$result = $mailer->send($reply);
                $result = "test";
                if ($result) {
                    $message->moveToMailBox($inboxes[$id]['prefix'] . $inboxes[$id]['folder']);
                    echo "<span style=\"background-color: #ff6d6d;\">Subject for: \"{$message->getSubject()}\" is <b>probably</b> spam. Sent auto-reply to sender, and moved mail to \"autoreplied\"-folder.</span><br>\n";
                    file_put_contents($inboxes[$id]['logfile'], "=> Rejected: \"{$message->getSubject()}\"\n\n", FILE_APPEND);
                    //echo $msg;
                }
            }
            elseif ($whitelisted == false && $spam == false) {
                echo "<span style=\"background-color: yellow;\">Subject for: \"{$message->getSubject()}\" did not match any spam rules.</span><br>\n";
                file_put_contents($inboxes[$id]['logfile'], "=> Accepted: \"{$message->getSubject()}\"\n\n", FILE_APPEND);
                //echo $msg;
            }
        }
    }
}

function isRecruiterSpam($rules, Message $message)
{
    $sum = 0;
    file_put_contents("../logs/spam.log", "### SpamCheck: ###\nSubject: " . $message->getSubject() . "\n", FILE_APPEND);
        
    $msgBody = $message->getMessageBody();
    $msgBody = str_replace("&#230;", "æ", $msgBody);
    $msgBody = str_replace("&#198;", "Æ", $msgBody);
    $msgBody = str_replace("&#248;", "ø", $msgBody);
    $msgBody = str_replace("&#216;", "Ø", $msgBody);
    $msgBody = str_replace("&#229;", "å", $msgBody);
    $msgBody = str_replace("&#197;", "Å", $msgBody);
    
    //file_put_contents("../logs/spam.log", $message->getMessageBody() . "\n", FILE_APPEND);
    //file_put_contents("../logs/spam.log", $msgBody . "\n", FILE_APPEND);
    
    foreach ($rules as $rule) {
        if (isset($rule['contains'])) {
            if (preg_match($rule['contains'], $message->getSubject())
                || preg_match($rule['contains'], $msgBody)
                //|| preg_match($rule['contains'], $message->getMessageBody())
            ) {
                $sum += $rule['points'];
                file_put_contents("../logs/spam.log", "Rule (" . $rule['points'] . "): " . $rule['contains'] . " (CONTAIN)\n", FILE_APPEND);
            }
        } else {
            if (isset($rule['from'])) {
                if (preg_match($rule['from'], $message->getOverview()->from)
                ) {
                    $sum += $rule['points'];
                    file_put_contents("../logs/spam.log", "Rule (" . $rule['points'] . "): " . $rule['from'] . " (FROM)\n", FILE_APPEND);
                }
            }
        }
        if ($sum > 99) {
            file_put_contents("../logs/spam.log", "Total spam-score: $sum\n", FILE_APPEND);
            return true;
        }
    }
    
    file_put_contents("../logs/spam.log", "Total spam-score: $sum\n", FILE_APPEND);
    return false;
}

function isWhitelisted($rules, Message $message) {
    foreach ($rules as $rule) {
        if (isset($rule['contains'])) {
            if (preg_match($rule['contains'], $message->getSubject())
                || preg_match($rule['contains'], $message->getMessageBody())
            ) {
                file_put_contents("../logs/spam.log", "### Whitelist: ###\nSubject: " . $message->getSubject() . "\nOn contain-rule: " . $rule['contains'] . "\n\n", FILE_APPEND);
                return true;
            }
        } else {
            if (isset($rule['from'])) {
                if (preg_match($rule['from'], $message->getOverview()->from)
                ) {
                    file_put_contents("../logs/spam.log", "### Whitelist: ###\nSubject: " . $message->getSubject() . "\nOn from-rule: " . $rule['from'] . "\n\n", FILE_APPEND);
                    return true;
                }
            }
        }
    }
    return false;
}
