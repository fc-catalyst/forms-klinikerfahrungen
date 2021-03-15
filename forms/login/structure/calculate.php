<?php

$resultsUser = calculate( $_POST['calc'], __DIR__.'/calculate.json' );
$resultsAdmin = calculate( $_POST['calc'], __DIR__.'/calculate-adm.json' );
$resultsUser['interesting-adm'] = $resultsAdmin['interesting'];

$reportUser = reportUser( $resultsUser );
$reportUserHidden = reportUserHidden( $resultsUser );
$reportUserExtend = reportUserExtend( $resultsUser, __DIR__.'/main.json' );
$reportAdmin = reportAdmin( $resultsAdmin, __DIR__.'/main.json' );

/*
$_POST['calc'] = [
    '01.02.2018',
    '85',
    '1000000',
    '200000',
    '100',
    '95'
];
//echo '<pre>';
print_r( $reportAdmin );
//echo '</pre>';
//*/

//*
$hash = md5( time() );

$newID = wp_insert_post([
    'post_author' => 1,
    'post_content' => ( $reportUser ? $reportUser : '' ),
    'post_title' => 'Dein Ergebnis',
    'post_excerpt' => '',
    'post_status' => 'publish',
    'post_type' => 'calculator',
    'comment_status' => 'closed',
    'ping_status' => 'closed',
    'post_name' => substr( $hash, 0, 8 )
]);

wp_insert_post([
    'ID' => $newID,
    'post_name' => substr( $hash . $newID, -8 )
]);

add_post_meta( $newID, 'fcp-calc--name', $_POST['calc-name'] );
add_post_meta( $newID, 'fcp-calc-protect', substr( $hash . $newID, -16 ) );
add_post_meta( $newID, 'fcp-calc-title',
    $resultsAdmin['score'] . ' ('.$resultsAdmin['min'].' - '.$resultsAdmin['max'].'€)'
);
add_post_meta( $newID, 'fcp-calc-admin-report', $reportAdmin );
add_post_meta( $newID, 'fcp-calc-user-rep-hidden', $reportUserHidden );
add_post_meta( $newID, 'fcp-calc-user-rep-extend', $reportUserExtend );

setcookie( 'fcp-calc-protect', substr( $hash . $newID, -16 ), 0, '/' );


$redirectURL = get_permalink( $newID );
//*/

function reportUser($data) {
    return '
    <p class="fcp-h-small">Nach unseren Berechnungen ist</p>
    <p class="fcp-h-big">dein Amazon FBA Business</p>
    <p class="fcp-h-avg">zwischen <b>'.$data['min'].' €</b> und <b>'.$data['max'].' €</b>* wert.</p>
    ';
}

function reportUserHidden($data) {

    return '
<table>
    <tbody>
        <tr>
            <td>
                Sicherheit:
            </td>
            <td>
                <b>'.$data['weighted'][0].'</b> ('.$data['improved'][0].' -&gt; '.$data['converted'][0].' * w'.$data['weights'][0].')
            </td>
        </tr>
        <tr>
            <td>
                Diversifizierung:
            </td>
            <td>
                <b>'.$data['weighted'][1].'</b> ('.$data['improved'][1].' -&gt; '.$data['converted'][1].' * w'.$data['weights'][1].')
            </td>
        </tr>
        <tr>
            <td>
                Profitabilität:
            </td>
            <td>
                <b>'.$data['weighted'][3].'</b> ('.$data['improved'][3].' -&gt; '.$data['converted'][3].' * w'.$data['weights'][3].')
            </td>
        </tr>
        <tr>
            <td>
                Konzentrierung:
            </td>
            <td>
                <b>'.$data['weighted'][4].'</b> ('.$data['improved'][4].' -&gt; '.$data['converted'][4].' * w'.$data['weights'][4].')
            </td>
        </tr>
        <tr>
            <td>
                Geschäftsmodell:
            </td>
            <td>
                <b>'.$data['weighted'][5].'</b> ('.$data['improved'][5].' -&gt; '.$data['converted'][5].' * w'.$data['weights'][5].')
            </td>
        </tr>
    </tbody>
</table>
<h3>So wurde dein Wert berechnet:</h3>
<ul>
<li>'.implode( "</li>\n<li>", $data['explain'] ).'</li>
</ul>

';
    
}

function reportUserExtend($data, $main = '') {

    if ( $main ) {
        $mains = @file_get_contents( $main );
        $mains = json_decode( $mains, true );

        foreach ( $mains as $v ) {
            foreach ( $v['fields'] as $w ) {
                $inits[] = $w;
            }
        }

        $endings = [ 'precent' => '%', 'euro' => '€' ];
        foreach ( $inits as $k => $v ) {
            $value = $data['post'][$k];
            $value = $v['type'] == 'select' ? $v['options'][$value] : $value;
            $value = $v['type'] == 'euro' ? number_format( $value, 0, ',', '.' ) : $value;
            $ending = $endings[$v['type']] ? ' '.$endings[$v['type']] : '';
            $fieldsValues[] = $v['title'].': '.$value.$ending;
        }
    } else {
        $fieldsValues = ['data not loaded'];
    }
    
    $q = 10;
    return '
<p>Hallo,
</p>
 
<p>vielen Dank, dass du unsere kostenlose Kurzbewertung deines Amazon Unternehmens genutzt hast. 
Wie angekündigt erhältst du untenstehend ausführlichere Details zu den fünf Haupt-Faktoren, die bei unserer Bewertung eine Rolle spielen.
</p>
 
<p><em>Hinweis: Der von uns ermittelte Wert ist lediglich als eine ungefähre Ersteinschätzung und <u>nicht</u> als verbindliches Kaufangebot zu verstehen. Für eine genaue Ermittlung des Unternehmenswerts ist eine eingehendere Prüfung durch unser Akquise-Team notwendig.</em>
</p>

<h2 align="center">Dein errechneter Multiplikator liegt zwischen<br>
<b>'.$data['minMult'].'</b> und <b>'.$data['maxMult'].'</b>
</h2>
 
<h2 align="center">Dein errechneter Unternehmenswert liegt zwischen <b>'.$data['min'].'&nbsp;€</b> und <b>'.$data['max'].'&nbsp;€</b>
</h2>

<h3>Folgende 5 Hauptkriterien wurden bewertet:</h3>


<h4>1. Stabilität und Sicherheit: '.round( $data['converted'][0] / $data['maxes'][0] * $q ).' von '.$q.' Punkten
</h4>
 
<p>Ein jahrelanges Bestehen des Unternehmens mit einer soliden Historie signalisiert Stabilität und Sicherheit.
</p>

<h4>2. Diversifizierung Verkaufskanäle: '.round( $data['converted'][1] / $data['maxes'][1] * $q ).' von '.$q.' Punkten
</h4>

<p>Je mehr Umsatz verhältnismäßig zum Gesamtumsatz über Amazon erwirtschaftet wird, desto abhängiger ist das Fortbestehen der Marke von Amazon. Dieses Risiko spiegelt sich in der Regel in einer niedrigeren Bewertung der Marke wider. Für uns bei Brands United ist dies jedoch kein Problem.
</p>

<h4>3. Profitabilität: '.round( $data['converted'][3] / $data['maxes'][3] * $q ).' von '.$q.' Punkten
</h4>

<p>Sofern deine Marke eine hohe Gewinnmarge erwirtschaftet, rechtfertigt dies auch einen höheren Wert deiner Marke. Bei einer genaueren Berechnung durch unser Expertenteam nehmen wir außerdem den „angepassten Gewinn“ als Grundlage. Hierbei addieren wir deinen Unternehmerlohn auf den buchhalterischen Gewinn (EBITDA).
</p>

<h4>4. Konzentration des Sortiments: '.round( $data['converted'][4] / $data['maxes'][4] * $q ).' von '.$q.' Punkten
</h4>

<p>Eine Diversifikation des Produktportfolios ist zunächst sinnvoll, um das Risiko zu streuen. Jedoch führen sehr große Produktsortimente unweigerlich zu mehr Komplexität.
</p>

<h4>5. Fokus auf Eigenmarken: '.round( $data['converted'][5] / $data['maxes'][5] * $q ).' von '.$q.' Punkten
</h4>

<p>Je mehr Umsatz deines Unternehmens über Eigenmarken generiert wird, desto positiver wirkt sich dies auf die Bewertung aus.
</p>

'.( $data['interesting-adm'] ? '

<p>Du möchtest eine <b>präzisere und detailliertere Bewertung deiner Marke von unserem Expertenteam</b> durchführen lassen und mehr zum Ankaufsprozess bei Brands United erfahren?
</p>

<p><strong>Wir freuen uns, mehr zu dir und deinem Unternehmen zu erfahren!</strong>
</p>
 
<p><a href="https://www.brands-united.de/erstkontakt" target="_blank">www.brands-united.de/erstkontakt</a>
</p>

' : '' ).'

<p>Oder informiere dich auf unserer Webseite: <a href="https://www.brands-united.de">www.brands-united.de</a>
</p>

<p>Unter folgendem Link kannst du zudem <a href="https://brands-united.de/wp-content/uploads/2020/10/Fuenf-Tipps-dein-Wert-deiner-Amazon-Marke-zu-erhoehen.pdf"><b>weiterführende Tipps zur Steigerung deines Markenwerts</b> herunterladen</a>.
</p>
 
<p>Viele Grüße,<br>
dein <em>Brands United - Team</em>
</p>

';
    
}

function reportAdmin($data, $main = '') {

    if ( $main ) {
        $mains = @file_get_contents( $main );
        $mains = json_decode( $mains, true );

        foreach ( $mains as $v ) {
            foreach ( $v['fields'] as $w ) {
                $inits[] = $w;
            }
        }

        $endings = [ 'precent' => '%', 'euro' => '€' ];
        foreach ( $inits as $k => $v ) {
            $value = $data['post'][$k];
            $value = $v['type'] == 'select' ? $v['options'][$value] : $value;
            $value = $v['type'] == 'euro' ? number_format( $value, 0, ',', '.' ) : $value;
            $ending = $endings[$v['type']] ? ' '.$endings[$v['type']] : '';
            $fieldsValues[] = $v['title'].': '.$value.$ending;
        }
    } else {
        $fieldsValues = ['data not loaded'];
    }
    
    
    return '
<h2>Gesamtbewertung: '.$data['score'].'</h2>
<h3>Bewertet mit: '.$data['min'].' - '.$data['max'].' €</h3>
    
<table>
    <tbody>
        <tr>
            <td>
                Sicherheit:
            </td>
            <td>
                <b>'.$data['weighted'][0].'</b> ('.$data['improved'][0].' -&gt; '.$data['converted'][0].' * w'.$data['weights'][0].')
            </td>
        </tr>
        <tr>
            <td>
                Diversifizierung:
            </td>
            <td>
                <b>'.$data['weighted'][1].'</b> ('.$data['improved'][1].' -&gt; '.$data['converted'][1].' * w'.$data['weights'][1].')
            </td>
        </tr>
        <tr>
            <td>
                Profitabilität:
            </td>
            <td>
                <b>'.$data['weighted'][3].'</b> ('.$data['improved'][3].' -&gt; '.$data['converted'][3].' * w'.$data['weights'][3].')
            </td>
        </tr>
        <tr>
            <td>
                Konzentrierung:
            </td>
            <td>
                <b>'.$data['weighted'][4].'</b> ('.$data['improved'][4].' -&gt; '.$data['converted'][4].' * w'.$data['weights'][4].')
            </td>
        </tr>
        <tr>
            <td>
                Geschäftsmodell:
            </td>
            <td>
                <b>'.$data['weighted'][5].'</b> ('.$data['improved'][5].' -&gt; '.$data['converted'][5].' * w'.$data['weights'][5].')
            </td>
        </tr>
    </tbody>
</table>


<h2>Gemachte Angaben:</h2>
<ul>
<li>'.implode( "</li>\n<li>", $fieldsValues ).'</li>
</ul>

<h3>So wurde dein Wert berechnet:</h3>
<ul>
<li>'.implode( "</li>\n<li>", $data['explain'] ).'</li>
</ul>

';
    
}

function calculate($post = [], $rules) {

    // include converting structure
    $rules = @file_get_contents( $rules );
    if ( !$rules ) {
        return 'Error 867';
    }
    $rules = json_decode( $rules, true );

    if ( count( $post ) !== count( $rules ) ) {
        return 'Error 572';
    }

    // keep $post clean
    $improved = $post;
    
    // speciffic values transform
    // date to num of months
    $improved[0] = ( time() - dmyToStamp( $post[0] ) ) / 60 / 60 / 24 / 365 * 12;
    // revenue to precent
    $improved[2] = $post[2] * 12;
    $improved[3] = $post[3] / $post[2] * 100;

    // recount values
    foreach( $improved as $k => $v ) {
        $converted[$k] = fcpConvert( $v, $rules[$k] );
    }
    
    // add weights
    foreach( $converted as $k => $v ) {
        $weights[$k] = $rules[$k]['weight'];
        $weighted[$k] = fcpWeight( $v, $rules[$k] );
    }

    // max values
    foreach( $converted as $k => $v ) {
        $maxes[$k] = max( $rules[$k]['options'] );
    }
    
    // count total
    $score = 0;
    foreach( $weighted as $v ) {
        if ( is_numeric( $v ) ) {
            $score += $v;
        }
    }

    // count rest
    $evaluated = ( $weighted[2][1] - $weighted[2][0] ) * $score / 10000 + $weighted[2][0];
    $evalMinFull = ( $evaluated - 0.2 ) * $post[3] * 12;
    $evalMaxFull = ( $evaluated + 0.2 ) * $post[3] * 12;
    
    $evalMin = fcpFormatPrice( $evalMinFull );
    $evalMax = fcpFormatPrice( $evalMaxFull );

    $explain[] = $post[2].' * 12 = '.$improved[2].' -> ['.$converted[2][0].', '.$converted[2][1].']';
    $explain[] = $converted[2][1].' - '.$converted[2][0].' = '.( $converted[2][1] - $converted[2][0] );
    $explain[] = $score.' * '.( $converted[2][1] - $converted[2][0] ).' / 10000 + '.$converted[2][0].' = '.$evaluated;
    $explain[] = "Min: ( ".$evaluated.' - 0.2 ) * '.$post[3].' * 12 = '.$evalMinFull;
    $explain[] = "Max: ( ".$evaluated.' + 0.2 ) * '.$post[3].' * 12 = '.$evalMaxFull;
    
    $interesting = true;
    if ( $score <= 6000 ) {
        $interesting = false;
    }
    if ( in_array( 0, $converted ) ) {
        $interesting = false;
    }
    
    return [
        'post' => $post,
        'improved' => $improved,
        'converted' => $converted,
        'weights' => $weights,
        'weighted' => $weighted,
        'maxes' => $maxes,
        'score' => $score,
        'minMult' => fcpFormatRate( $evaluated - 0.2 ),
        'maxMult' => fcpFormatRate( $evaluated + 0.2 ),
        'min' => $evalMin,
        'max' => $evalMax,
        'explain' => $explain,
        'interesting' => $interesting
    ];
}

function fcpFormatPrice($price) {
    $length = strlen( $price );
    $roundBy = floor( $length / 2 );
    return number_format( round( $price, -$roundBy ), 0, ',', '.' );
}

function fcpFormatRate($rate) {
    return number_format( round( $rate, 2 ), 2, ',', '.' );
}

function fcpConvert($post, $rules) {
    if ( !$rules['compare'] || !$rules['options'] ) {
        return $post;
    }
    
    $result = null;
    
    if ( $rules['compare'] === 'less' ) {
    
        foreach ( $rules['options'] as $k => $v ) {
            if ( is_numeric( $k ) && $post+0 < $k ) {
                $result = $v;
                // if ( $result === -1 ) { return 'Not interested'; }
                break;
            }
        }
        
        if ( $result === null && isset( $rules['options']['higher'] ) ) {
            $result = $rules['options']['higher'];
        }

    }

    return $result !== null ? $result : $post;
}

function fcpWeight($post, $rules) {
    if ( !$rules['compare'] || !$rules['options'] ) {
        return $post;
    }
    
    $result = null;

    if ( $rules['weight'] ) {
        $result = $post * $rules['weight'];
    }
    
    return $result !== null ? $result : $post;
}

function dmyToStamp($date) {

    $d = DateTime::createFromFormat( 'd.m.y', $date );

    if ( $d === false ) {
        $d = DateTime::createFromFormat( 'd.m.Y', $date );
    }

    if ( $d !== false ) {
        return $d->getTimestamp();
    }

    return false;
}
