###v0.3.2
- added **[Hermes_Filter](https://github.com/sentfanwyaerda/Hermes/blob/master/manual/Filter.md)** and **[Hermes_Analyse](https://github.com/sentfanwyaerda/Hermes/blob/master/manual/Analyse.md)**
- fixed *REMOTE_ADDR_CC* auto-commit if GeoIP available

###v0.3.1
- made **Hermes()** function trigger the operations of the **Hermes class**
- fixed **Hermes::getLastestScrollID()** when db is empty

###v0.3.0
- Started rewrite from previous experimental versions. Excluded non tested methods, for now.
- **Hermes::getIdentity()**: generates a base128 *identity* of current visitor (used to be base75). The base can be changed by **HERMES_IDENTITY_BASE**. Also provides a method of rebuilding an *identity* based upon
```php
Hermes::getIdentity($_SERVER['REMOTE_ADDR'], $_SERVER['REMOTE_HOST'], $_SERVER['HTTP_USER_AGENT'], $_SERVER['HTTP_ACCEPT_LANGUAGE']);
```
	- Because *identity* is expected to be saved in JSON, **HERMES_BASE_FIX_CHARACTER** replaces **"**.
- **Hermes::getCurrentScrollID()**: is able to select its current log-file. Assignes new on new timeperiod or when HERMES_SCROLL_SIZE_LIMIT is exceded.
