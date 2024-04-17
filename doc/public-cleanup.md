# Cleanup Plan before public release

## Intro

Two alternatives : 
* Squash history
* Cleanup historry
  * see: https://docs.github.com/en/authentication/keeping-your-account-and-data-secure/removing-sensitive-data-from-a-repository  
    OR : https://rtyley.github.io/bfg-repo-cleaner/

## Cleanup list

* .env.dist 
  * remove all files or invalidate keys : 
    * FIREBASE_API_KEY
* censorship on commit messages ?
