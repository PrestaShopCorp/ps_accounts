# Claude Review — Template de sortie

> **Usage** : Ce fichier est lu par Claude lors de la review automatique des PR.
> Il n'est PAS un template de PR et ne doit PAS apparaitre dans les formulaires GitHub.

---

## Regles de mise en forme

- Utiliser pleinement le Markdown GitHub (headings, tableaux, blocs de code, separateurs).
- **Pas d'emojis decoratifs** : pas d'emoji devant chaque titre, ligne ou paragraphe.
- Emojis autorises (usage fonctionnel uniquement) :
  - Verdict : `✅` (approuve), `❌` (changements demandes), `⚠️` (a corriger)
  - Checklists : `☐` / `☑`
- Chaque issue a son propre heading `###` suivi d'un separateur `---`.
- Quand c'est pertinent, proposer un fix concret dans un bloc ` ```diff `.
- Le tableau recapitulatif est **toujours** present en fin de review.
- Le footer indiquant comment demander une re-review est present **uniquement dans la review initiale** (pas dans la re-review).
- La balise `<!-- claude-review-done:SHA -->` est **obligatoire** en toute fin de body.
  Le SHA est celui du HEAD actuel, fourni dans le prompt.

---

## Template — Review avec issues

```markdown
## Review Claude

**Verdict** : ✅ Approuve | ❌ Changements demandes | ⚠️ A corriger

**Scope** : zones concernees par cette PR

---

### [CRITIQUE] Titre court du probleme

**Fichier** : `chemin/fichier.ext:ligne`

Description concise (1-2 phrases). Pourquoi c'est un probleme et comment le corriger.

```diff
- ancien code problematique
+ correction proposee
```

---

### [MAJEUR] Titre court du probleme

**Fichier** : `chemin/fichier.ext:ligne`

Description concise. Reference a la convention violee (CLAUDE.md section X).

---

### [MINEUR] Titre court du probleme

**Fichier** : `chemin/fichier.ext:ligne`

Description concise.

---

### [SUGGESTION] Titre court

**Fichier** : `chemin/fichier.ext:ligne`

Description concise.

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

**Verdict** : ✅ Approuve

**Scope** : zones concernees par cette PR

---

RAS — le code est propre, les conventions sont respectees.

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

**Verdict** : ✅ Approuve | ❌ Changements demandes | ⚠️ A corriger

**Scope** : commits depuis `<sha_precedent>`

---

### Remarques precedentes

- ☑ Remarque corrigee
- ☐ Remarque non corrigee (details ci-dessous)

---

(meme structure que la review initiale pour les nouvelles issues)

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

## Verdicts et actions `gh pr review`

Le verdict determine la commande `gh pr review` a utiliser :

| Verdict | Quand | Commande |
|---------|-------|----------|
| ✅ Approuve | 0 CRITIQUE + 0 MAJEUR | `--approve` |
| ⚠️ A corriger | MAJEUR sans CRITIQUE | `--comment` |
| ❌ Changements demandes | Au moins 1 CRITIQUE | `--request-changes` |
