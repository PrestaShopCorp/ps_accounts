# Claude Review — Template de sortie

> **Usage** : Ce fichier est lu par Claude lors de la review automatique des PR.
> Il n'est PAS un template de PR et ne doit PAS apparaitre dans les formulaires GitHub.
>
> Il ne decrit que la **mise en forme**. La methode de review (perimetre de lecture,
> criteres, checklist compatibilite, classification, limites) vit dans
> [`.claude/agents/code-review.md`](../.claude/agents/code-review.md) — source unique.

---

## Regles de mise en forme

- Utiliser pleinement le Markdown GitHub (headings, tableaux, blocs de code, separateurs).
- **Pas d'emojis decoratifs** : pas d'emoji devant chaque titre, ligne ou paragraphe.
- Emojis autorises (usage fonctionnel uniquement) :
  - Verdict : `✅` (rien a signaler), `⚠️` (a corriger), `❌` (bloquant)
  - Checklists : `☐` / `☑`
- Chaque issue a son propre heading `###` suivi d'un separateur `---`.
- **Description compacte** : voir la section `### Limite par section (OBLIGATOIRE)` de `.claude/agents/code-review.md` — source unique de la regle.
- Tout finding CRITIQUE ou MAJEUR porte une ligne `Scenario :` (version PS/PHP + etat initial → enchainement → etat final faux). Voir le paragraphe 3 de l'agent.
- Le chemin du fichier peut etre inline dans le heading (`### [NIVEAU] Titre — \`file:line\``) ou sur une ligne `**Fichier** :` separee.
- Quand c'est pertinent, proposer un fix concret dans un bloc ` ```diff `.
- Le tableau recapitulatif est **toujours** present en fin de review.
- Le footer indiquant comment demander une re-review est present **uniquement dans la review initiale** (pas dans la re-review).
- La balise `<!-- claude-review-done:SHA -->` est **obligatoire** en toute fin de body.
  Le SHA est celui du HEAD actuel, fourni dans le prompt.
- Le verdict est **textuel uniquement** : la review est toujours postee avec
  `gh pr review --comment`, jamais `--approve` ni `--request-changes`
  (voir `## Verdict et action` en fin de fichier).

---

## Template — Review avec issues

```markdown
## Review Claude

**Verdict** : ✅ Rien a signaler | ⚠️ A corriger | ❌ Bloquant

**Scope** : zones concernees par cette PR

---

### [CRITIQUE] Titre court — `chemin/fichier.ext:ligne`

≤ 500 car., ligne `Scenario` et bloc `diff` inclus — le probleme et la correction attendue.

Scenario : PS/PHP concernes, etat initial → enchainement (avec les `fichier:ligne` traverses) → etat final faux.

```diff
- ancien code problematique
+ correction proposee
```

---

### [MAJEUR] Titre court — `chemin/fichier.ext:ligne`

≤ 500 car., ligne `Scenario` et bloc `diff` inclus — reference de convention entre parentheses (`CLAUDE.md` §3).

Scenario : idem — obligatoire aussi en MAJEUR.

---

### [MINEUR] Titre court — `chemin/fichier.ext:ligne`

≤ 500 car., bloc `diff` inclus.

---

### [SUGGESTION] Titre court — `chemin/fichier.ext:ligne`

≤ 500 car., bloc `diff` inclus.

---

### Revue humaine requise

Zones sensibles touchees et pourquoi (critere 9 — revue humaine).
Omettre la section entiere si aucune zone sensible n'est touchee.

---

### Resume

| Niveau | Nombre |
|--------|--------|
| CRITIQUE | 0 |
| MAJEUR | 0 |
| MINEUR | 0 |
| SUGGESTION | 0 |

---

> Pour demander une re-review apres corrections, re-ajoutez le label `need-claude-review`.

<!-- claude-review-done:HEAD_SHA -->
```

---

## Template — Review sans issue

```markdown
## Review Claude

**Verdict** : ✅ Rien a signaler

**Scope** : zones concernees par cette PR

---

RAS — le code est propre, les conventions sont respectees.

---

### Revue humaine requise

Zones sensibles touchees et pourquoi (critere 9 — revue humaine).
Omettre la section entiere si aucune zone sensible n'est touchee.

---

### Resume

| Niveau | Nombre |
|--------|--------|
| CRITIQUE | 0 |
| MAJEUR | 0 |
| MINEUR | 0 |
| SUGGESTION | 0 |

---

> Pour demander une re-review apres corrections, re-ajoutez le label `need-claude-review`.

<!-- claude-review-done:HEAD_SHA -->
```

---

## Template — Re-review

Lors d'une re-review, adapter le heading :

```markdown
## Re-review Claude

**Verdict** : ✅ Rien a signaler | ⚠️ A corriger | ❌ Bloquant

**Scope** : commits depuis `<sha_precedent>`

---

### Remarques precedentes

- ☑ [NIVEAU] Titre de la remarque — corrigee
- ☐ [MINEUR] Titre de la remarque — inchangee
- ☐ [CRITIQUE] Titre de la remarque — inchangee — `chemin/fichier.ext:ligne`
  Le fix attendu, en 1 ligne.

---

(meme structure que la review initiale pour les nouvelles issues)

---

### Revue humaine requise

Inchangee depuis la review precedente.
(ou la section complete, uniquement si elle a change ;
omettre la section entiere si aucune zone sensible n'est touchee)

---

### Resume

| Niveau | Nombre |
|--------|--------|
| CRITIQUE | 0 |
| MAJEUR | 0 |
| MINEUR | 0 |
| SUGGESTION | 0 |

---

> **Quota atteint** — cette PR a utilise ses 2 reviews automatiques (initiale + re-review). Les prochaines revisions devront etre faites manuellement.

<!-- claude-review-done:HEAD_SHA -->
```

---

## Verdict et action

Le verdict ne change PAS la commande : la review est **toujours** postee avec
`gh pr review <numero> --comment --body-file /tmp/review.md`.

| Verdict | Quand |
|---------|-------|
| ✅ Rien a signaler | 0 CRITIQUE + 0 MAJEUR |
| ⚠️ A corriger | MAJEUR sans CRITIQUE |
| ❌ Bloquant | Au moins 1 CRITIQUE |
