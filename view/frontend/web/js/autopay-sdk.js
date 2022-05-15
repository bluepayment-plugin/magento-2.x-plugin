(function(window, document) {
    const buttonOrigin = 'https://app-accept.autopay.pl';
    const mainConfig = {};
    const iframeEl = document.createElement('iframe');

    function createAndAppendIframe() {
        console.log('PLUGIN => Creating button iframe url');
        let iframeSrc = 'https://app-accept.autopay.pl/checkout/button.html?';
        let iframeParent = document.body;

        if (mainConfig.button) {
            const buttonConfig = mainConfig.button;

            if (buttonConfig.element) {
                const element = buttonConfig.element instanceof HTMLElement ? buttonConfig.element : document.querySelector(buttonConfig.element);
                if (element) {
                    iframeParent = element;
                }
            }
            if (buttonConfig.language) {
                iframeSrc += 'language=' + buttonConfig.language + '&';
            }
            if (buttonConfig.theme) {
                iframeSrc += 'theme=' + buttonConfig.theme + '&';
            }
            if (buttonConfig.text) {
                iframeSrc += 'text=' + buttonConfig.text;
            }
        }

        iframeEl.src = iframeSrc;
        iframeEl.height = '42px';
        iframeEl.style.border = 'none';
        iframeEl.style.overflow = 'hidden';
        iframeEl.addEventListener('load', () => {
            console.log('PLUGIN => Connecting with iframe');
            iframeEl.contentWindow.postMessage('{"status":"CONNECT"}', buttonOrigin);
        })
        console.log('PLUGIN => Append iframe to provided element');
        iframeParent.append(iframeEl);
    }

    function processError(context, error) {
        try {
            console.log('PLUGIN => Running onError handler');
            if (typeof context.onError === 'function') {
                context.onError()
            }
        } catch (e) {
            console.warn('There was an error at onError callback.')
        }
    }

    class AutopayCheckout {
        constructor(config) {
            console.log('PLUGIN => Autopay Checkout process start');
            if (!config || typeof config !== 'object') {
                throw new Error('Invalid config');
            }
            console.log('PLUGIN => Saving configuration');
            Object.assign(mainConfig, config);
            createAndAppendIframe();
            window.addEventListener('message', async (e) => {
                let eventData;
                if (e.origin !== buttonOrigin) {
                    return;
                }
                try {
                    eventData = JSON.parse(e.data);
                } catch(e) {}
                if (!eventData) {
                    return;
                }

                console.log(eventData);

                if (eventData.status === 'BUTTON_CLICK') {
                    try {
                        console.log('PLUGIN => Running onBeforeCheckout handler');
                        if (typeof this.onBeforeCheckout === 'function') {
                            await this.onBeforeCheckout();
                        }
                    }
                    catch(e) {
                        return processError(this, e);
                    }

                    console.log('PLUGIN => Passing configuration to iframe');
                    console.log(mainConfig.transaction);
                    iframeEl.contentWindow.postMessage('{"status":"SEND_DATA","data":' + JSON.stringify(mainConfig.transaction) + '}', buttonOrigin)

                    try {
                        console.log('PLUGIN => Running onCheckout handler');
                        if (typeof this.onCheckout === 'function') {
                            this.onCheckout()
                        }
                    }
                    catch(e) {
                        processError(this, e);
                    }
                }

                if (eventData.status === 'REGISTER') {
                    try {
                        console.log('PLUGIN => Running onRegister handler');
                        if (typeof this.onRegister === 'function') {
                            this.onRegister()
                        }
                    } catch (e) {
                        return processError(this, e);
                    }
                }

                if (eventData.status === 'SUCCESS') {
                    try {
                        console.log('PLUGIN => Running onSuccess handler');
                        if (typeof this.onSuccess === 'function') {
                            this.onSuccess()
                        }
                    } catch (e) {
                        return processError(this, e);
                    }
                }

                if (eventData.status === 'ERROR') {
                    processError(this);
                }
            })
        }

        setTransactionData(transaction) {
            console.log('PLUGIN => Setting transaction data');
            if (!mainConfig.transaction) {
                mainConfig.transaction = {};
            }
            Object.assign(mainConfig.transaction, transaction);
            return this;
        }
    }

    window = window || this;
    if (typeof window.autopay === 'undefined') {
        console.log('PLUGIN => Creating autopay API in window');
        window.autopay = {};
    }
    if (!(window.autopay.checkout instanceof AutopayCheckout)) {
        window.autopay.checkout = AutopayCheckout;
    }
})(window, document);
