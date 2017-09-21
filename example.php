<?php
include_once "reporter/convenience.php";
include_once "reporter/reporter.php";
include_once "reporter/reporterFrontend.php";
include_once 'settings.php';
?>
<html lang="en">
<head>
    <style>
        body {
            font-family: "Consolas", "Bitstream Vera Sans Mono", "Courier New", Courier, monospace;
        }
        .analytics-not-stored {
            background-color: silver;
        }

        .analytics-stored {
            background-color: lightskyblue;
            color: white;
        }

        .analytics-match {
            background-color: green;
            color: white;
        }



        .analytics-not-found {
            background-color: yellow;
            color: black;
        }

        .analytics-no-match.analytics-stored {
            background-color: red;
            color: white;
        }
    </style>
</head>
<body>

<table border="1" cellpadding="1" cellspacing="0">
    <thead>
    <tr>
        <td>Domain</td>
        <td>Live</td>
        <td>Speed</td>
        <td>Usability</td>
        <td>Framework</td>
        <td>www</td>
        <td>@</td>
        <td>Mail</td>
        <td style="background-color: #ADE7D9">Analytics Stored</td>
        <td>Analytics Found</td>
        <td style="background-color: #ADE7D9">Analytics Expired</td>
        <td>Robots.txt</td>
    </tr>
    </thead>

    <?php
    foreach ($rows as $domain_array):

        $domain = $domain_array["domain_name"];
        $domain_where = new \reporter\_reporterFrontend("http://" . $domain);

        $domain_where->request_psi();
        $psi = $domain_where->response['psi'];
        $formatted = $domain_where;
        $analytics = $domain_array['analytics'];
        $ua_id = $ga_obj->get_ga_implemented($domain); //Call to a function to extract details
        if ($ua_id == NULL) {
            $analytics_found = NULL;
        } else {
            $analytics_found = $ua_id[0][0];
        }
        ?>
        <tr>
            <td><?= $domain ?></td>
            <td>
                <?php

                if (isset($psi->error->code)) {
                    echo "Offline:" . $psi->error->code;
                } elseif($psi->responseCode == 200) {
                    echo "Live";
                } else {
                    print "Problem.";
                }

                ?>
            </td>
            <td><?=$psi->ruleGroups->SPEED->score?>%</td>
            <td><?=$psi->ruleGroups->USABILITY->score?>%</td>
            <td>
                <?php
                print isDomainAvailible($domain);
                ?>
            </td>
            <td>
                <?php
                $ip_www = gethostbyname('www.' . $domain);
                echo $ip_www;
                ?>
            </td>
            <td>
                <?php
                $ip_at = gethostbyname($domain);
                echo $ip_at;
                ?>
            </td>
            <td>
                <?php
                $ip_mail = gethostbyname('mail.' . $domain);
                echo $ip_mail;
                ?>
            </td>
            <?php
            $class_analytics = [];
            if ($analytics) {
                $class_analytics[] = "analytics-stored";
            } else {
                $class_analytics[] = "analytics-not-stored";
            }

            if ($analytics_found) {
                $class_analytics[] = "analytics-found";
            } else {
                $class_analytics[] = "analytics-not-found";
            }

            if ($analytics_found == $analytics && $analytics_found != null) {
                $class_analytics[] = "analytics-match";
            } else {
                $class_analytics[] = "analytics-no-match";
            }
            $class_stored = [];
            $class = [];
            $class = implode(" ", $class_analytics);

            print "<td >" . $analytics . "</td>";
            print "<td class=\"$class\">" . $analytics_found . "</td>";
            ?>
            <td>2018/05/15
            </td>
            <td>Allow/Disallow
            </td>
        </tr>
    <?php
    endforeach;
    ?>
    </table>
</body>
</html>

