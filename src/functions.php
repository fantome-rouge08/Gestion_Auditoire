<?php
/**
 * src/functions.php - Logique métier, lecture CSV et validation
 */

/**
 * Lit un fichier CSV et le transforme en tableau associatif.
 * Gère les erreurs de fichiers manquants ou de lignes malformées.
 */
function lire_csv($chemin_fichier, $colonnes_attendues) {
    if (!file_exists($chemin_fichier)) {
        error_log("Erreur : Fichier introuvable - $chemin_fichier");
        return [];
    }

    $donnees = [];
    if (($handle = fopen($chemin_fichier, "r")) !== FALSE) {
        $ligne_num = 0;
        while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
            $ligne_num++;
            // Vérification si la ligne a assez de colonnes
            if (count($data) < count($colonnes_attendues)) {
                error_log("Erreur ligne $ligne_num dans $chemin_fichier : Colonnes manquantes.");
                continue;
            }
            
            $item = [];
            foreach ($colonnes_attendues as $index => $cle) {
                $item[$cle] = trim($data[$index]);
            }
            $donnees[] = $item;
        }
        fclose($handle);
    }
    return $donnees;
}

/**
 * Charge les salles depuis le fichier CSV.
 */
function charger_salles($f) { return lire_csv($f, ['id', 'designation', 'capacite']); }

/**
 * Charge les promotions depuis le fichier CSV.
 */
function charger_promotions($f) { return lire_csv($f, ['id', 'libelle', 'effectif']); }

/**
 * Charge les cours depuis le fichier CSV (5 colonnes attendues).
 */
function charger_cours($f) { 
    return lire_csv($f, ['id', 'intitule', 'vol', 'type', 'id_rattachement']); 
}

/**
 * Charge les options depuis le fichier CSV.
 */
function charger_options($f) { return lire_csv($f, ['id', 'nom']); }

/**
 * Vérifie si une salle est disponible pour un jour et un créneau donnés.
 */
function salle_disponible($planning, $id_salle, $jour, $creneau) {
    foreach ($planning as $affectation) {
        if ($affectation['jour'] === $jour && $affectation['creneau'] === $creneau && $affectation['id_salle'] === $id_salle) {
            return false;
        }
    }
    return true;
}

/**
 * Vérifie si un groupe (promotion) est libre sur un créneau donné.
 */
function creneau_libre_groupe($planning, $id_groupe, $jour, $creneau) {
    foreach ($planning as $affectation) {
        if ($affectation['jour'] === $jour && $affectation['creneau'] === $creneau && $affectation['id_groupe'] === $id_groupe) {
            return false;
        }
    }
    return true;
}

/**
 * Vérifie si la capacité de la salle est suffisante pour l'effectif d'un groupe.
 */
function capacite_suffisante($salles, $id_salle, $effectif) {
    foreach ($salles as $salle) {
        if ($salle['id'] === $id_salle) return (int)$salle['capacite'] >= (int)$effectif;
    }
    return false;
}

/**
 * Génère une proposition de planning hebdomadaire sans conflit.
 * Stratégie : Affectation par priorité d'effectif décroissant.
 */
function generer_planning($salles, $promotions, $cours, $options, $creneaux_disponibles) {
    $planning = [];
    $jours = ['Lundi', 'Mardi', 'Mercredi', 'Jeudi', 'Vendredi'];

    $promotions_map = [];
    foreach ($promotions as $p) { $promotions_map[$p['id']] = $p; }
    
    // Tri des cours pour placer les plus gros groupes en premier (contrainte de capacité)
    usort($cours, function($a, $b) use ($promotions_map) {
        $effA = isset($promotions_map[$a['id_rattachement']]) ? $promotions_map[$a['id_rattachement']]['effectif'] : 50;
        $effB = isset($promotions_map[$b['id_rattachement']]) ? $promotions_map[$b['id_rattachement']]['effectif'] : 50;
        return (int)$effB - (int)$effA;
    });

    foreach ($cours as $c) {
        $groupe_id = $c['id_rattachement'];
        $effectif = isset($promotions_map[$groupe_id]) ? $promotions_map[$groupe_id]['effectif'] : 50;
        $planifie = false;

        foreach ($jours as $jour) {
            foreach ($creneaux_disponibles as $creneau) {
                foreach ($salles as $salle) {
                    if (capacite_suffisante($salles, $salle['id'], $effectif) &&
                        salle_disponible($planning, $salle['id'], $jour, $creneau) &&
                        creneau_libre_groupe($planning, $groupe_id, $jour, $creneau)) {
                        
                        $planning[] = [
                            'jour' => $jour, 'creneau' => $creneau,
                            'id_salle' => $salle['id'], 'id_cours' => $c['id'], 'id_groupe' => $groupe_id
                        ];
                        $planifie = true;
                        break 3;
                    }
                }
            }
        }
    }
    return $planning;
}

/**
 * Sauvegarde le planning généré dans un fichier CSV.
 */
function sauvegarder_planning($planning, $chemin_fichier) {
    $f = fopen($chemin_fichier, 'w');
    foreach ($planning as $p) {
        $heures = explode('-', $p['creneau']);
        $ligne = [$p['jour'], $heures[0], $heures[1] ?? '', $p['id_salle'], $p['id_cours'], $p['id_groupe']];
        fputcsv($f, $ligne);
    }
    fclose($f);
    return true;
}

/**
 * Recharge le planning depuis le fichier CSV.
 */
function charger_planning($chemin_fichier) {
    $data = lire_csv($chemin_fichier, ['jour', 'debut', 'fin', 'id_salle', 'id_cours', 'id_groupe']);
    $planning = [];
    foreach ($data as $d) {
        $planning[] = [
            'jour' => $d['jour'],
            'creneau' => $d['debut'] . '-' . $d['fin'],
            'id_salle' => $d['id_salle'],
            'id_cours' => $d['id_cours'],
            'id_groupe' => $d['id_groupe']
        ];
    }
    return $planning;
}

/**
 * Affiche le planning sous forme de tableaux regroupés par jour et auditoire.
 */
function afficher_planning_html($planning, $liste_cours = [], $liste_salles = []) {
    if (empty($planning)) return "<p class='alert'>Aucun planning disponible.</p>";
    
    // Création de dictionnaires pour un accès rapide aux noms complets
    $noms_cours = [];
    foreach ($liste_cours as $c) $noms_cours[$c['id']] = $c['intitule'];
    
    $noms_salles = [];
    foreach ($liste_salles as $s) $noms_salles[$s['id']] = $s['designation'];

    $jours = ['Lundi', 'Mardi', 'Mercredi', 'Jeudi', 'Vendredi'];
    $creneaux = [];
    foreach ($planning as $p) { if (!in_array($p['creneau'], $creneaux)) $creneaux[] = $p['creneau']; }
    sort($creneaux);

    // Liste unique des salles présentes dans le planning
    $salles_utilisees = [];
    foreach ($planning as $p) { if (!in_array($p['id_salle'], $salles_utilisees)) $salles_utilisees[] = $p['id_salle']; }
    sort($salles_utilisees);

    $html = "";

    foreach ($jours as $j) {
        // On ne génère le tableau que s'il y a des cours ce jour-là
        $cours_du_jour = array_filter($planning, function($p) use ($j) { return $p['jour'] === $j; });
        if (empty($cours_du_jour)) continue;

        $html .= "<div class='day-section'>";
        $html .= "<h3><svg width='20' height='20' viewBox='0 0 24 24' fill='none' stroke='currentColor' stroke-width='2' stroke-linecap='round' stroke-linejoin='round' style='margin-right:8px;'><rect x='3' y='4' width='18' height='18' rx='2' ry='2'/><line x1='16' y1='2' x2='16' y2='6'/><line x1='8' y1='2' x2='8' y2='6'/><line x1='3' y1='10' x2='21' y2='10'/></svg> $j</h3>";
        $html .= "<div class='table-responsive'><table class='planning-table'><thead><tr><th>Créneau</th>";
        
        foreach ($salles_utilisees as $s_id) {
            $html .= "<th>" . ($noms_salles[$s_id] ?? $s_id) . "</th>";
        }
        $html .= "</tr></thead><tbody>";

        foreach ($creneaux as $c) {
            $html .= "<tr><td><strong>$c</strong></td>";
            foreach ($salles_utilisees as $s_id) {
                $html .= "<td>";
                foreach ($planning as $p) {
                    if ($p['jour'] === $j && $p['creneau'] === $c && $p['id_salle'] === $s_id) {
                        $nom_complet = $noms_cours[$p['id_cours']] ?? $p['id_cours'];
                        $color_index = abs(crc32($p['id_groupe'])) % 8;
                        $html .= "<div class='planning-item color-$color_index'><strong>$nom_complet</strong><br><small>{$p['id_groupe']}</small></div>";
                    }
                }
                $html .= "</td>";
            }
            $html .= "</tr>";
        }
        $html .= "</tbody></table></div></div>";
    }
    
    return $html;
}

/**
 * Supprime une salle du fichier CSV et nettoie le planning associé.
 */
function supprimer_salle($id_salle, $chemin_fichier) {
    $salles = charger_salles($chemin_fichier);
    $f = fopen($chemin_fichier, 'w');
    foreach ($salles as $s) {
        if ($s['id'] !== $id_salle) {
            fputcsv($f, [$s['id'], $s['designation'], $s['capacite']]);
        }
    }
    fclose($f);

    // Nettoyage automatique du planning
    $planning_file = dirname($chemin_fichier) . '/planning.csv';
    if (file_exists($planning_file)) {
        $planning = charger_planning($planning_file);
        $planning_nettoye = array_filter($planning, function($p) use ($id_salle) {
            return $p['id_salle'] !== $id_salle;
        });
        sauvegarder_planning($planning_nettoye, $planning_file);
    }
    return true;
}

/**
 * Modifie les informations d'une salle (nom ou capacité).
 */
function modifier_salle($id_salle, $nouvelle_capacite, $chemin_fichier) {
    $salles = charger_salles($chemin_fichier);
    $f = fopen($chemin_fichier, 'w');
    foreach ($salles as $s) {
        if ($s['id'] === $id_salle) {
            fputcsv($f, [$s['id'], $s['designation'], $nouvelle_capacite]);
        } else {
            fputcsv($f, [$s['id'], $s['designation'], $s['capacite']]);
        }
    }
    fclose($f);
    return true;
}

/**
 * Détecte les conflits (salle ou groupe) dans un fichier de planning.
 */
function detecter_conflits_txt($chemin_txt) {
    $planning = charger_planning($chemin_txt);
    $conflits = [];
    $occ_salle = []; $occ_groupe = [];

    foreach ($planning as $p) {
        $k_s = "{$p['jour']}-{$p['creneau']}-{$p['id_salle']}";
        $k_g = "{$p['jour']}-{$p['creneau']}-{$p['id_groupe']}";
        if (isset($occ_salle[$k_s])) $conflits[] = "Salle {$p['id_salle']} occupée par {$occ_salle[$k_s]} et {$p['id_cours']} le {$p['jour']} à {$p['creneau']}";
        if (isset($occ_groupe[$k_g])) $conflits[] = "Groupe {$p['id_groupe']} a deux cours : {$occ_groupe[$k_g]} et {$p['id_cours']} le {$p['jour']} à {$p['creneau']}";
        $occ_salle[$k_s] = $p['id_cours'];
        $occ_groupe[$k_g] = $p['id_cours'];
    }
    return $conflits;
}

/**
 * Génère un rapport d'occupation des salles en pourcentage.
 */
function generer_rapport_occupation($chemin_txt, $salles) {
    $planning = charger_planning($chemin_txt);
    $total_possible = 10; // Hypothèse : 2 créneaux par jour sur 5 jours
    $stats = [];
    foreach ($salles as $s) {
        $occ = 0;
        foreach ($planning as $p) { if ($p['id_salle'] == $s['id']) $occ++; }
        $stats[] = "{$s['id']} : $occ occupés, " . ($total_possible - $occ) . " libres (" . ($occ/$total_possible*100) . "%)";
    }
    file_put_contents(dirname($chemin_txt) . '/rapport_occupation.txt', implode("\n", $stats));
    return $stats;
}
?>
