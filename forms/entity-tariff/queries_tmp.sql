#slow(15s)-----------------------------------------

SELECT posts.ID,
    mt0.meta_value AS till, #entity-tariff-till
    IF ( mt4.meta_key = "entity-tariff-next", mt4.meta_value, NULL ) AS tariff_next,
    IF ( mt6.meta_key = "entity-payment-status-next", mt6.meta_value, NULL ) AS status_next,
    IF ( mt8.meta_key = "entity-timezone", mt8.meta_value, NULL ) AS timezone
FROM `wpfcp_posts` AS posts
  LEFT JOIN `wpfcp_postmeta` AS mt0 ON ( posts.ID = mt0.post_id )
  LEFT JOIN `wpfcp_postmeta` AS mt1 ON ( posts.ID = mt1.post_id )
  LEFT JOIN `wpfcp_postmeta` AS mt2 ON ( posts.ID = mt2.post_id )
  LEFT JOIN `wpfcp_postmeta` AS mt3 ON ( posts.ID = mt3.post_id AND mt3.meta_key = "entity-timezone-bias" )
  LEFT JOIN `wpfcp_postmeta` AS mt4 ON ( posts.ID = mt4.post_id )
  LEFT JOIN `wpfcp_postmeta` AS mt5 ON ( posts.ID = mt5.post_id AND mt5.meta_key = "entity-tariff-next" )
  LEFT JOIN `wpfcp_postmeta` AS mt6 ON ( posts.ID = mt6.post_id )
  LEFT JOIN `wpfcp_postmeta` AS mt7 ON ( posts.ID = mt7.post_id AND mt7.meta_key = "entity-payment-status-next" )
  LEFT JOIN `wpfcp_postmeta` AS mt8 ON ( posts.ID = mt8.post_id )
  LEFT JOIN `wpfcp_postmeta` AS mt9 ON ( posts.ID = mt9.post_id AND mt9.meta_key = "entity-timezone" )
WHERE 1 = 1 AND (
  ( mt0.meta_key = "entity-tariff-till" AND mt1.meta_value != "0" )
  AND
  ( mt1.meta_key = "entity-tariff-till" AND CAST( IF ( mt2.meta_key = "entity-timezone-bias", mt0.meta_value - mt2.meta_value, mt0.meta_value ) AS SIGNED ) < 1639572503 )
  AND
  ( mt2.meta_key = "entity-timezone-bias" OR mt3.post_id IS NULL )
  AND
  ( mt4.meta_key = "entity-tariff-next" OR mt5.post_id IS NULL )
  AND
  ( mt6.meta_key = "entity-payment-status-next" OR mt7.post_id IS NULL )
  AND
  ( mt8.meta_key = "entity-timezone" OR mt9.post_id IS NULL )
) AND posts.post_type IN ("clinic", "doctor") GROUP BY posts.ID

#experiment still slow (8s)-----------------------------------------

SELECT SQL_CALC_FOUND_ROWS  wpfcp_posts.ID FROM wpfcp_posts  LEFT JOIN wpfcp_postmeta ON ( wpfcp_posts.ID = wpfcp_postmeta.post_id )  LEFT JOIN wpfcp_postmeta AS mt1 ON ( wpfcp_posts.ID = mt1.post_id )  LEFT JOIN wpfcp_postmeta AS mt2 ON ( wpfcp_posts.ID = mt2.post_id AND mt2.meta_key = "entity-timezone" )  LEFT JOIN wpfcp_postmeta AS mt3 ON ( wpfcp_posts.ID = mt3.post_id )  LEFT JOIN wpfcp_postmeta AS mt4 ON ( wpfcp_posts.ID = mt4.post_id AND mt4.meta_key = "entity-timezone-bias" )  LEFT JOIN wpfcp_postmeta AS mt5 ON ( wpfcp_posts.ID = mt5.post_id )  LEFT JOIN wpfcp_postmeta AS mt6 ON ( wpfcp_posts.ID = mt6.post_id AND mt6.meta_key = "entity-tariff-next" )  LEFT JOIN wpfcp_postmeta AS mt7 ON ( wpfcp_posts.ID = mt7.post_id )  LEFT JOIN wpfcp_postmeta AS mt8 ON ( wpfcp_posts.ID = mt8.post_id AND mt8.meta_key = "entity-payment-status-next" ) WHERE 1=1  AND ( 
  ( wpfcp_postmeta.meta_key = "entity-tariff-till" AND wpfcp_postmeta.meta_value < "1639582970" ) 
  AND 
  ( 
    mt1.meta_key = "entity-timezone" 
    OR 
    mt2.post_id IS NULL
  ) 
  AND 
  ( 
    mt3.meta_key = "entity-timezone-bias" 
    OR 
    mt4.post_id IS NULL
  ) 
  AND 
  ( 
    mt5.meta_key = "entity-tariff-next" 
    OR 
    mt6.post_id IS NULL
  ) 
  AND 
  ( 
    mt7.meta_key = "entity-payment-status-next" 
    OR 
    mt8.post_id IS NULL
  )
) AND wpfcp_posts.post_type IN ("clinic", "doctor") AND (wpfcp_posts.post_status = "publish" OR wpfcp_posts.post_status = "dp-rewrite-republish" OR wpfcp_posts.post_status = "future" OR wpfcp_posts.post_status = "draft" OR wpfcp_posts.post_status = "pending" OR wpfcp_posts.post_author = 2 AND wpfcp_posts.post_status = "private") GROUP BY wpfcp_posts.ID ORDER BY wpfcp_posts.post_date DESC LIMIT 0, 12

#experiment fast (0.4s)-----------------------------------------

SELECT * FROM
(
SELECT wpfcp_posts.ID FROM wpfcp_posts  LEFT JOIN wpfcp_postmeta ON ( wpfcp_posts.ID = wpfcp_postmeta.post_id )  LEFT JOIN wpfcp_postmeta AS mt1 ON ( wpfcp_posts.ID = mt1.post_id )  LEFT JOIN wpfcp_postmeta AS mt2 ON ( wpfcp_posts.ID = mt2.post_id AND mt2.meta_key = "entity-timezone-bias" )  LEFT JOIN wpfcp_postmeta AS mt3 ON ( wpfcp_posts.ID = mt3.post_id )  LEFT JOIN wpfcp_postmeta AS mt4 ON ( wpfcp_posts.ID = mt4.post_id AND mt4.meta_key = "entity-tariff-next" )  LEFT JOIN wpfcp_postmeta AS mt5 ON ( wpfcp_posts.ID = mt5.post_id )  LEFT JOIN wpfcp_postmeta AS mt6 ON ( wpfcp_posts.ID = mt6.post_id AND mt6.meta_key = "entity-payment-status-next" ) WHERE 1=1  AND ( 
  ( wpfcp_postmeta.meta_key = "entity-tariff-till" AND wpfcp_postmeta.meta_value < "1639582162" ) 
  AND 
  ( 
    mt1.meta_key = "entity-timezone-bias" 
    OR 
    mt2.post_id IS NULL
  ) 
  AND 
  ( 
    mt3.meta_key = "entity-tariff-next" 
    OR 
    mt4.post_id IS NULL
  ) 
  AND 
  ( 
    mt5.meta_key = "entity-payment-status-next" 
    OR 
    mt6.post_id IS NULL
  )
) AND wpfcp_posts.post_type IN ("clinic", "doctor") AND (wpfcp_posts.post_status = "publish" OR wpfcp_posts.post_status = "dp-rewrite-republish" OR wpfcp_posts.post_status = "future" OR wpfcp_posts.post_status = "draft" OR wpfcp_posts.post_status = "pending" OR wpfcp_posts.post_author = 2 AND wpfcp_posts.post_status = "private") GROUP BY wpfcp_posts.ID ORDER BY wpfcp_posts.post_date DESC LIMIT 0, 12
) AS s1
JOIN (
SELECT wpfcp_posts.ID FROM wpfcp_posts  LEFT JOIN wpfcp_postmeta ON ( wpfcp_posts.ID = wpfcp_postmeta.post_id )  LEFT JOIN wpfcp_postmeta AS mt1 ON ( wpfcp_posts.ID = mt1.post_id )  LEFT JOIN wpfcp_postmeta AS mt2 ON ( wpfcp_posts.ID = mt2.post_id AND mt2.meta_key = "entity-timezone-bias" )  LEFT JOIN wpfcp_postmeta AS mt3 ON ( wpfcp_posts.ID = mt3.post_id )  LEFT JOIN wpfcp_postmeta AS mt4 ON ( wpfcp_posts.ID = mt4.post_id AND mt4.meta_key = "entity-timezone" ) WHERE 1=1  AND ( 
  ( wpfcp_postmeta.meta_key = "entity-tariff-till" AND wpfcp_postmeta.meta_value < "1639582273" ) 
  AND 
  ( 
    mt1.meta_key = "entity-timezone-bias" 
    OR 
    mt2.post_id IS NULL
  ) 
  AND 
  ( 
    mt3.meta_key = "entity-timezone" 
    OR 
    mt4.post_id IS NULL
  )
) AND wpfcp_posts.post_type IN ("clinic", "doctor") AND (wpfcp_posts.post_status = "publish" OR wpfcp_posts.post_status = "dp-rewrite-republish" OR wpfcp_posts.post_status = "future" OR wpfcp_posts.post_status = "draft" OR wpfcp_posts.post_status = "pending" OR wpfcp_posts.post_author = 2 AND wpfcp_posts.post_status = "private") GROUP BY wpfcp_posts.ID ORDER BY wpfcp_posts.post_date DESC LIMIT 0, 12
) AS s2
ON s1.ID = s2.ID

experiment nexts 1 (0.56s)-----------------------------------------

SELECT posts.ID,
    mt0.meta_value AS till, #entity-tariff-till
    IF ( mt4.meta_key = "entity-tariff-next", mt4.meta_value, NULL ) AS tariff_next,
    IF ( mt6.meta_key = "entity-payment-status-next", mt6.meta_value, NULL ) AS status_next
FROM `wpfcp_posts` AS posts
  LEFT JOIN `wpfcp_postmeta` AS mt0 ON ( posts.ID = mt0.post_id )
  LEFT JOIN `wpfcp_postmeta` AS mt1 ON ( posts.ID = mt1.post_id )
  LEFT JOIN `wpfcp_postmeta` AS mt2 ON ( posts.ID = mt2.post_id AND mt2.meta_key = "entity-timezone-bias" )
  LEFT JOIN `wpfcp_postmeta` AS mt3 ON ( posts.ID = mt3.post_id )
  LEFT JOIN `wpfcp_postmeta` AS mt4 ON ( posts.ID = mt4.post_id AND mt4.meta_key = "entity-tariff-next" )
  LEFT JOIN `wpfcp_postmeta` AS mt5 ON ( posts.ID = mt5.post_id )
  LEFT JOIN `wpfcp_postmeta` AS mt6 ON ( posts.ID = mt6.post_id AND mt6.meta_key = "entity-payment-status-next" )
WHERE
  1 = 1
  AND (
    ( mt0.meta_key = "entity-tariff-till" AND CAST( IF ( mt2.meta_key = "entity-timezone-bias", mt0.meta_value - mt2.meta_value, mt0.meta_value ) AS SIGNED ) < 1639582162 )
    AND
    ( mt1.meta_key = "entity-timezone-bias" OR mt2.post_id IS NULL )
    AND
    ( mt3.meta_key = "entity-tariff-next" OR mt4.post_id IS NULL )
    AND 
    ( mt5.meta_key = "entity-payment-status-next" OR mt6.post_id IS NULL )
  )
  AND posts.post_type IN ("clinic", "doctor")
GROUP BY posts.ID


experiment nexts 2 (0.57s)-----------------------------------------

SELECT sq1.ID, till, tariff_next, status_next, timezone
FROM (
    SELECT
        posts.ID,
        mt0.meta_value AS till, #entity-tariff-till
        IF ( mt4.meta_key = "entity-tariff-next", mt4.meta_value, NULL ) AS tariff_next,
        IF ( mt6.meta_key = "entity-payment-status-next", mt6.meta_value, NULL ) AS status_next
    FROM `wpfcp_posts` AS posts
    LEFT JOIN `wpfcp_postmeta` AS mt0 ON ( posts.ID = mt0.post_id )
    LEFT JOIN `wpfcp_postmeta` AS mt1 ON ( posts.ID = mt1.post_id )
    LEFT JOIN `wpfcp_postmeta` AS mt2 ON ( posts.ID = mt2.post_id AND mt2.meta_key = "entity-timezone-bias" )
    LEFT JOIN `wpfcp_postmeta` AS mt3 ON ( posts.ID = mt3.post_id )
    LEFT JOIN `wpfcp_postmeta` AS mt4 ON ( posts.ID = mt4.post_id AND mt4.meta_key = "entity-tariff-next" )
    LEFT JOIN `wpfcp_postmeta` AS mt5 ON ( posts.ID = mt5.post_id )
    LEFT JOIN `wpfcp_postmeta` AS mt6 ON ( posts.ID = mt6.post_id AND mt6.meta_key = "entity-payment-status-next" )
    WHERE
    1 = 1
    AND (
        ( mt0.meta_key = "entity-tariff-till" AND CAST( IF ( mt2.meta_key = "entity-timezone-bias", mt0.meta_value - mt2.meta_value, mt0.meta_value ) AS SIGNED ) < 1639582162 )
        AND
        ( mt1.meta_key = "entity-timezone-bias" OR mt2.post_id IS NULL )
        AND
        ( mt3.meta_key = "entity-tariff-next" OR mt4.post_id IS NULL )
        AND 
        ( mt5.meta_key = "entity-payment-status-next" OR mt6.post_id IS NULL )
    )
    AND posts.post_type IN ("clinic", "doctor")
    GROUP BY posts.ID
) AS sq1
JOIN (
    SELECT
        posts.ID,
        IF ( mt4.meta_key = "entity-timezone", mt4.meta_value, NULL ) AS timezone
    FROM `wpfcp_posts` AS posts
    LEFT JOIN `wpfcp_postmeta` AS mt0 ON ( posts.ID = mt0.post_id )
    LEFT JOIN `wpfcp_postmeta` AS mt1 ON ( posts.ID = mt1.post_id )
    LEFT JOIN `wpfcp_postmeta` AS mt2 ON ( posts.ID = mt2.post_id AND mt2.meta_key = "entity-timezone-bias" )
    LEFT JOIN `wpfcp_postmeta` AS mt3 ON ( posts.ID = mt3.post_id )
    LEFT JOIN `wpfcp_postmeta` AS mt4 ON ( posts.ID = mt4.post_id AND mt4.meta_key = "entity-timezone" )
    WHERE
    1 = 1
    AND (
        ( mt0.meta_key = "entity-tariff-till" AND CAST( IF ( mt2.meta_key = "entity-timezone-bias", mt0.meta_value - mt2.meta_value, mt0.meta_value ) AS SIGNED ) < 1639582162 )
        AND
        ( mt1.meta_key = "entity-timezone-bias" OR mt2.post_id IS NULL )
        AND
        ( mt3.meta_key = "entity-timezone" OR mt4.post_id IS NULL )
    )
    AND posts.post_type IN ("clinic", "doctor")
    GROUP BY posts.ID
) AS sq2
ON sq1.ID = sq2.ID

experiment nexts 3 (0,15s)-----------------------------------------SUCCESS

SET @till_time = 1639582162;

SELECT sq1.ID, till, tariff_next, status_next, timezone_name

FROM (
    SELECT
        mt0.meta_value AS till, #entity-tariff-till
        posts.ID,
        IF ( mt4.meta_key = "entity-tariff-next", mt4.meta_value, NULL ) AS tariff_next
    FROM wpfcp_posts AS posts
        LEFT JOIN wpfcp_postmeta AS mt0 ON ( posts.ID = mt0.post_id )
        LEFT JOIN wpfcp_postmeta AS mt1 ON ( posts.ID = mt1.post_id )
        LEFT JOIN wpfcp_postmeta AS mt2 ON ( posts.ID = mt2.post_id AND mt2.meta_key = "entity-timezone-bias" )
        LEFT JOIN wpfcp_postmeta AS mt3 ON ( posts.ID = mt3.post_id )
        LEFT JOIN wpfcp_postmeta AS mt4 ON ( posts.ID = mt4.post_id AND mt4.meta_key = "entity-tariff-next" )
    WHERE
        1 = 1
        AND (
            ( mt0.meta_key = "entity-tariff-till" AND CAST( IF ( mt2.meta_key = "entity-timezone-bias", mt0.meta_value - mt2.meta_value, mt0.meta_value ) AS SIGNED ) < @till_time )
            AND
            ( mt1.meta_key = "entity-timezone-bias" OR mt2.post_id IS NULL )
            AND
            ( mt3.meta_key = "entity-tariff-next" OR mt4.post_id IS NULL )
        )
        AND posts.post_type IN ("clinic", "doctor")
    GROUP BY posts.ID
) AS sq1

JOIN (
    SELECT
        posts.ID,
        IF ( mt4.meta_key = "entity-payment-status-next", mt4.meta_value, NULL ) AS status_next
    FROM wpfcp_posts AS posts
        LEFT JOIN wpfcp_postmeta AS mt0 ON ( posts.ID = mt0.post_id )
        LEFT JOIN wpfcp_postmeta AS mt1 ON ( posts.ID = mt1.post_id )
        LEFT JOIN wpfcp_postmeta AS mt2 ON ( posts.ID = mt2.post_id AND mt2.meta_key = "entity-timezone-bias" )
        LEFT JOIN wpfcp_postmeta AS mt3 ON ( posts.ID = mt3.post_id )
        LEFT JOIN wpfcp_postmeta AS mt4 ON ( posts.ID = mt4.post_id AND mt4.meta_key = "entity-payment-status-next" )
    WHERE
        1 = 1
        AND (
            ( mt0.meta_key = "entity-tariff-till" AND CAST( IF ( mt2.meta_key = "entity-timezone-bias", mt0.meta_value - mt2.meta_value, mt0.meta_value ) AS SIGNED ) < @till_time )
            AND
            ( mt1.meta_key = "entity-timezone-bias" OR mt2.post_id IS NULL )
            AND
            ( mt3.meta_key = "entity-payment-status-next" OR mt4.post_id IS NULL )
        )
        AND posts.post_type IN ("clinic", "doctor")
    GROUP BY posts.ID
) AS sq2

JOIN (
    SELECT
        posts.ID,
        IF ( mt4.meta_key = "entity-timezone", mt4.meta_value, NULL ) AS timezone_name
    FROM wpfcp_posts AS posts
        LEFT JOIN wpfcp_postmeta AS mt0 ON ( posts.ID = mt0.post_id )
        LEFT JOIN wpfcp_postmeta AS mt1 ON ( posts.ID = mt1.post_id )
        LEFT JOIN wpfcp_postmeta AS mt2 ON ( posts.ID = mt2.post_id AND mt2.meta_key = "entity-timezone-bias" )
        LEFT JOIN wpfcp_postmeta AS mt3 ON ( posts.ID = mt3.post_id )
        LEFT JOIN wpfcp_postmeta AS mt4 ON ( posts.ID = mt4.post_id AND mt4.meta_key = "entity-timezone" )
    WHERE
        1 = 1
        AND (
            ( mt0.meta_key = "entity-tariff-till" AND CAST( IF ( mt2.meta_key = "entity-timezone-bias", mt0.meta_value - mt2.meta_value, mt0.meta_value ) AS SIGNED ) < @till_time )
            AND
            ( mt1.meta_key = "entity-timezone-bias" OR mt2.post_id IS NULL )
            AND
            ( mt3.meta_key = "entity-timezone" OR mt4.post_id IS NULL )
        )
        AND posts.post_type IN ("clinic", "doctor")
    GROUP BY posts.ID
) AS sq3
ON sq1.ID = sq2.ID AND sq1.ID = sq3.ID