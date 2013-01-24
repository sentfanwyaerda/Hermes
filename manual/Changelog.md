###v0.3.0
- Started rewrite from previous experimental versions. Excluded non tested methods, for now.
- **Hermes::getIdentity()**: generates a base128 *identity* of current visitor (used to be base75). The base can be changed by **HERMES_IDENTITY_BASE**. Also provides a method of rebuilding an *identity* based upon
```php
Hermes::getIdentity($_SERVER['REMOTE_ADDR'], $_SERVER['REMOTE_HOST'], $_SERVER['HTTP_USER_AGENT'], $_SERVER['HTTP_ACCEPT_LANGUAGE']);
```
	- Because *identity* is expected to be saved in JSON, **HERMES_BASE_FIX_CHARACTER** replaces **"**.
- **Hermes::getCurrentScrollID()**: is able to select its current log-file. Assignes new on new timeperiod or when HERMES_SCROLL_SIZE_LIMIT is exceded.
