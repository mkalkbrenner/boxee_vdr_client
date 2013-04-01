<?php

/**
 * @file now.rss.php creates a rss feed of all channels of a vdr server
 *       including "now playing" from epg if available
 *
 * @copyright Copyright 2013 Markus Kalkbrenner
 * @license GPLv2
 * @author Markus Kalkbrenner
 */

setlocale(LC_ALL, 'de_DE.UTF-8');

$ip = $argv[1];
$limit = !empty($argv[2]) ? $argv[2] : -1;

print '<?xml version="1.0" encoding="UTF-8" ?>
<rss version="2.0">

<channel>
<title>Boxee VDR Client</title>
<description></description>
<link>https://github.com/mkalkbrenner/boxee_vdr_client</link>

<language>de-de</language>
<copyright>Markus Kalkbrenner</copyright>

<image>
<title>Boxee VDR Client</title>
<url>http://' . $ip . ':8080/vdr_server/images/logo.png</url>
<link>https://github.com/mkalkbrenner/boxee_vdr_client</link>
</image>

';

if ($stream = stream_socket_client("tcp://$ip:6419")) {
  $c = '';
  $t = '';
  $s = '';
  fputs($stream, "LSTE now\n");
  while ($line = stream_get_line($stream, 1024, "\n")) {
    $parts = explode(' ', $line);
    switch (array_shift($parts)) {
      case '215-C':
        $c = array_shift($parts);
        $t = implode(' ', $parts);
        break;
      case '215-T':
        if ($title = implode(' ', $parts)) {
          $t .= ': ' . $title;
        }
        break;
      case '215-S':
        $s = implode(' ', $parts);
        break;
      case '215-c':
        print "<item>\n<title>$t</title>\n<description>$s</description>\n<link>http://$ip:3000/$c.ts</link>\n</item>\n\n";
        if (0 == --$limit) {
          break(2);
        }
        $c = '';
        $t = '';
        $s = '';
        break;
      case '215': // End of EPG data
      case '221': // server closing connection
        break(2);
    }
  }
  fclose($stream);
}

print "</rss>\n";
