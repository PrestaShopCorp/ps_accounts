# Upgrade strategy

## Hook (current solution)
* We must improve stability and avoid sending events if we detect an SQL error
* Maintaining upgrade status of the module is a core responsibility
* We take a risk every time we does an upgrade (crash, spamming shops ....)
* Heavy to use a hook triggered every time we display an admin page
* Having a look at the mixpanel we can see we have a regular number of shops spamming with disconnection events

## Upgrade script with Curl & SQL
* Fastidious & we must duplicate rw code into every upgrade script

## Autoload Hack
* positive benefits / risk balance
* seemless upgrade
* limited risk (only upgrade phase can be affected)
* NOT WORKING on PS1.7 (not reaching upgrade script)


# Dependencies with the core & between modules
## Dependencies collision
### fix: Scoping
# Dependencies between modules (sharing functionalities)
## fix: MBO
# Upgrade issues
## early hooks
### fix: stop using `actionDispatcherBefore` like hooks
## not up-to-date autoload
### fix: enforce autoload script


