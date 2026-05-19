-- ============================================================
-- Migration SFx20/21/22 : ajouter une colonne pour la lettre
-- de motivation saisie en texte (alternative au PDF)
-- ============================================================

ALTER TABLE `candidature`
  ADD COLUMN `lm_texte` TEXT DEFAULT NULL AFTER `lm`;
