Site
====
Site is a Website framework built on top of
[Swat](https://github.com/silverorange/swat). Site provides several features
in one monolothic package:

 - general application framework
 - command line application framework
 - web application framework
 - web request routing
 - image processing
 - media processing
 - account sign in and session management
 - ad referral processing
 - attachment uploading
 - cdn management
 - user commenting

SiteApplication
---------------
This represents an application. There are two main child classes:

 - SiteWebApplication, and
 - SiteCommandLineApplication

SiteApplicationModule
---------------------
Reusable features of applications (web or CLI) that should be available
wherever there is application context are provided using a module interface.

Example provided modules are:

 - database
 - config
 - memcache
 - cd
 - messages (cross-request session messages)

Modules declare their dependencies and are initialized using a tree sorting
algorithm. For example, the `messages` module depends on the `session` module.

SitePage and SitePageDecorator
------------------------------
Pages represent route endpoints (URLs) in a web application. The URL path is
often referred to as `$source`. Each page has several lifecycle hooks that can
be overridden:

 - `init()` - initialize objects that may be common to both the `process()` and
   build methods. Validating HTTP GET parameters can be done here.
 - `process()` - runs between `init()` and `build()`. This method is indended
   to contain request logic that might prevent the page from being rendered.
   Handling HTTP POST parameters is done here.
 - `build()` - use this method to render content and pass it to the page layout.
   If the page request does not cause a redirect, this method is used to build
   the response.
 - `finalize()` - use this method to collect HTML head entries or perform any
   other post-build operations.

Decorators are composable objects that implement the page interface. They can
enable horizontal reuse of features.

SiteLayout
----------
In a web application, each page has an associated layout. The page and layout
are created and configured in `SiteWebApplication::getPage()`. One or more
`SitePageFactory` objects may be used to select the correct page object and
layout for a request.

The page sets properties on the layout's `$data` object. These properties can
be used directly inside layout templates. Templates use pure immediate-mode
PHP.

Like pages, layouts have request lifecycle hooks. These hooks run before the
page hooks of the same name:

 - `init()`
 - `process()`
 - `build()`
 - `finalize()`
 - `complete()` - this hook is only present in layouts and allows using data
   from the page's `finalize()` hook to build layout template values. It runs
   after the page's `finalize()` hook.

Additional Documentation
------------------------
 * [SiteEditPage Design](https://github.com/silverorange/site/wiki/SiteEditPage)
 * [SiteImage Usage](https://github.com/silverorange/site/wiki/SiteImage)

Installation
------------
Make sure the silverorange composer repository is added to the `composer.json`
for the project and then run:

```sh
composer require silverorange/site
```
