Contributing to Woketo
======================

Code style
----------

Woketo follows most parts of the coding style of [PSR](http://www.php-fig.org/psr/) & [Symfony](http://symfony.com/doc/current/contributing/code/standards.html) except these points:

1. `return` clause must be explicit. That means that when you return null, you should precise `return null;`
2. Comments in docblock should be aligned
3. We put spaces around the `.` of concatenation and the `=` of default parameter in functions
4. Functions are prefixed by `\` to call them from global namespace explicitly

*This code-style is a recommendation but can be pointed out in a pull-request code review.*

Tests
-----

### Every patch or new behavior must have its own unit test (phpunit), even if the functional test exists via [AutobahnTestsuite](http://autobahn.ws/testsuite/).

*This is a good-enough motivation to* ***not*** *merge a pull-request.*

Documentation
-------------

Each time you can relate a method to a part of the [RFC](https://tools.ietf.org/html/rfc6455). Please add the most precise link to the concerned part of the RFC.

Commits and squashes
--------------------

**Do not squash or amend your commits** when a PR is reviewed. This breaks the diff on github and makes it painful to review.
Prefer addition of new commit on the branch.

We can squash on merge.

*Notice that this does not imply PR that is not review ATM.*
