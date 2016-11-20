Development documentation
=========================

*This documentation is here to help people that want to contribute doing it.*

Glossary
--------


* **ws-frame** or `Frame` is a data frame defined by the RFC in section [5.2](https://tools.ietf.org/html/rfc6455#section-5.2) (prefer usage of `ws-frame`)
* **bin-frame** or `frame`  is a raw data frame received from the distant socket, it may contains a partial ws-frames or many.
* **Message** is a context object that may contain many ws-frames or none but a buffer of a ws-frame.
