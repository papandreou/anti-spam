<?php

require_once '../vendor/autoload.php';
require_once '../accounts.php';

use Fetch\Message;
use Fetch\Server;

$rules = [
    ['from' => '@explorerec\.com', 'points' => 100],
    ['from' => '@netvision\.net', 'points' => 100],
    ['from' => '@shitmail\.me', 'points' => 100],
    ['contains' => 'finding IT opportunities', 'points' => 100],
    ['contains' => 'PHP specialists?', 'points' => 80],
    ['contains' => 'startups?', 'points' => 10],
    ['contains' => 'saw your profile on GitHub', 'points' => 50],
    ['contains' => 'explore-group\.com', 'points' => 100],
    ['contains' => 'new position', 'points' => 20],
    ['contains' => 'urgent(ly)? need', 'points' => 30],
    ['contains' => 'huge plus', 'points' => 15],
    ['contains' => 'full-stack developer', 'points' => 30],
    ['contains' => 'interviews?', 'points' => 20],
    ['contains' => 'CV', 'points' => 60],
    ['contains' => 'skills', 'points' => 10],
    ['contains' => 'candidates?', 'points' => 20],
    ['contains' => 'Beneficial (offer|proposition)', 'points' => 100],
    ['contains' => '(We offer.*|Open) vacancy', 'points' => 100],
    ['contains' => 'part-time employment', 'points' => 100],
    ['contains' => 'liderlig|knald', 'points' => 100],
    ['contains' => 'Kærligheden øer lige om hjørnet, og vi kan maske hjælpe dig', 'points' => 100],
    ['contains' => 'Hvem behøver nogen billeder af kendte, nar du har det her', 'points' => 100],
    ['contains' => '(Er du)? klar til (kærlighed|at mode den eneste ene)?', 'points' => 100],
    ['contains' => 'Cooperation with.*(company|firm)', 'points' => 100],
    ['contains' => 'Begynd at chatte med en hottie NU', 'points' => 100],
    ['contains' => 'We offer', 'points' => 30],
    ['contains' => '\% off today', 'points' => 100],
    ['contains' => 'Salary.*\$.*(day|week|month|year)?', 'points' => 100],
    ['contains' => 'Klar til at mode den ENESTE ene', 'points' => 100],
    ['contains' => 'Din naeste date venter pa dig', 'points' => 30],
    ['contains' => 'Skal vi ikke modes', 'points' => 30],
    ['contains' => 'Work with us', 'points' => 100],
    ['contains' => 'Flexible schedule', 'points' => 40],
    ['contains' => 'For CV \#', 'points' => 100],
    ['contains' => 'sex', 'points' => 20],
    ['contains' => 'Big.*\% Off', 'points' => 80],
    ['contains' => 'Lønnen er på', 'points' => 80], // Why does this one not work? Does other æøå-rules work?
    ['contains' => '\d+.?eur|dkk|dollars?', 'points' => 60],
    //['contains' => 'stort internationalt firma', 'points' => 80],
];

$whitelistRules = [
    ['contains' => '/sent via our-little-app/i'],
    ['from' => '/flygenring.net/i'],
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
    foreach ($rules as $rule) {
        if (isset($rule['contains'])) {
            if (preg_match($rule['contains'], $message->getSubject())
                || preg_match($rule['contains'], $message->getMessageBody())
            ) {
                $sum += $rule['points'];
                file_put_contents("../logs/spam.log", "Rule (" . $rule['points'] . "): " . $rule['contains'] . " (CONTAIN)\n", FILE_APPEND);
            }
        } else {
            if (isset($rule['from'])) {
                if (preg_match($rule['from'], $message->getOverview()->from)
                ) {
                    $sum += $rule['points'];
                    file_put_contents("../logs/spam.log", "Rule (" . $rule['points'] . "): " . $rule['contains'] . " (FROM)\n", FILE_APPEND);
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
                    file_put_contents("../logs/spam.log", "### Whitelist: ###\nSubject: " . $message->getSubject() . "\nOn from-rule: " . $rule['contains'] . "\n\n", FILE_APPEND);
                    return true;
                }
            }
        }
    }
    return false;
}
