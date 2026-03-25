<?php
// ============================================================
//  includes/queries.php — SQL query generators
//  Replaces MySQL views with reusable PHP functions
// ============================================================

/**
 * Generates SQL fragment for v_ultima_taratura view
 * Returns the latest calibration per machine with expiry status
 * 
 * @param string $alias Table alias for the derived table (default: 'vt')
 * @return string SQL fragment for use as derived table/subquery
 */
function sql_ultima_taratura(string $alias = 'vt'): string
{
    return "
        SELECT m.id AS macchinario_id, 
            m.nome AS macchinario_nome, 
            m.codice_seriale AS codice_seriale, 
            m.qr_token AS qr_token, 
            m.intervallo_mesi AS intervallo_mesi,
            m.tipo_categoria AS tipo_categoria,
            m.unita_misura AS unita_misura,
            r.nome AS reparto,
            t.id AS taratura_id,
            t.data_inserimento AS data_inserimento,
            t.data_scadenza AS data_scadenza,
            t.tecnico AS tecnico,
            t.ente_certificatore AS ente_certificatore,
            t.numero_certificato AS numero_certificato,
            t.esito AS esito,
            t.note AS note,
            t.pdf_path AS pdf_path,
            DATEDIFF(t.data_scadenza, CURDATE()) AS giorni_alla_scadenza,
            CASE 
              WHEN t.data_scadenza IS NULL THEN NULL
              WHEN t.data_scadenza < CURDATE() THEN 'scaduta'
              WHEN DATEDIFF(t.data_scadenza, CURDATE()) <= 30 THEN 'in_scadenza'
              ELSE 'valida'
            END AS stato_scadenza
        FROM macchinari m
        JOIN reparti r ON r.id = m.reparto_id
        LEFT JOIN tarature t ON t.id = (
            SELECT id FROM tarature 
            WHERE tarature.macchinario_id = m.id
            ORDER BY tarature.data_inserimento DESC, tarature.id DESC
            LIMIT 1
        )
        WHERE m.attivo = 1
    ) AS {$alias}";
}

/**
 * Generates SQL fragment for v_tarature_in_scadenza view
 * Returns the latest calibration per machine expiring within admin preavviso days
 * Only includes records not yet notified and machines that are active
 * 
 * @return string SQL fragment for use as derived table/subquery
 */
function sql_tarature_in_scadenza(): string
{
    return "
        SELECT 
            t.id AS taratura_id, 
            m.nome AS macchinario_nome, 
            m.codice_seriale AS codice_seriale, 
            r.nome AS reparto,
            t.data_scadenza AS data_scadenza,
            DATEDIFF(t.data_scadenza, CURDATE()) AS giorni_rimanenti,
            t.notifica_inviata AS notifica_inviata,
            a.email AS email_admin,
            a.giorni_preavviso AS giorni_preavviso
        FROM tarature t
        JOIN macchinari m ON m.id = t.macchinario_id
        JOIN reparti r ON r.id = m.reparto_id
        JOIN admin a ON a.id = 1
        WHERE t.id IN (SELECT MAX(id) FROM tarature GROUP BY macchinario_id)
        AND t.data_scadenza >= CURDATE()
        AND DATEDIFF(t.data_scadenza, CURDATE()) <= a.giorni_preavviso
        AND t.notifica_inviata = 0
        AND m.attivo = 1
    ) AS v_tarature_in_scadenza";
}
