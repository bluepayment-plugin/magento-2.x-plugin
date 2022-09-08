var SDKConfigurationBmHub = {
    setConfiguration: function(t, e, a, l) {
        localStorage["sdk-merchant"] || (l = {
            merchantId: t,
            creditValue: 1e3,
            productPriceClass: e.toString(),
            paymentPriceClass: a.toString(),
            paymentSelectorClass: l.toString()
        },
            localStorage.setItem("sdk-merchant", JSON.stringify(l)))
    }
};
class SDK {
    buttonSendCalculatorData;
    buttonDecreaseValue;
    buttonIncreaseValue;
    loader;
    sdkCalculatorContainer;
    sliderInput;
    sliderInputContainer;
    installmentSlider;
    installmentInput;
    rangeLabels;
    activeCalculatorLabel;
    installmentNumberSummary;
    installmentSummary;
    rrsoSummary;
    totalCostSummary;
    installmentNumberSuffix;
    inputSuffix;
    logosContainer;
    promotionalInstallments;
    regularlInstallmentMin;
    regularlInstallmentMax;
    installmentNumberValue;
    installmentSliderValue;
    initialMonth;
    initialInstallment;
    initialRRSO;
    tenors = [];
    merchantData;
    token;
    getVariables() {
        this.buttonSendCalculatorData = document.getElementById("sendCalculatorData"),
            this.buttonDecreaseValue = document.querySelector(".sdk-calculator__button--decrease"),
            this.buttonIncreaseValue = document.querySelector(".sdk-calculator__button--increase"),
            this.loader = document.getElementById("sdk-loading"),
            this.sdkCalculatorContainer = document.getElementById("sdk-calculator__loan-container"),
            this.sliderInput = document.getElementById("sliderInput"),
            this.sliderInputContainer = document.getElementById("sliderInputContainer"),
            this.installmentSlider = document.getElementById("installmentSlider"),
            this.installmentInput = document.getElementById("installmentInput"),
            this.rangeLabels = document.getElementById("rangeLabels"),
            this.installmentNumberSummary = document.getElementById("installmentNumber"),
            this.installmentSummary = document.getElementById("installment"),
            this.rrsoSummary = document.getElementById("rrso"),
            this.totalCostSummary = document.getElementById("totalCost"),
            this.installmentNumberSuffix = document.getElementById("installmentNumberSuffix"),
            this.inputSuffix = document.getElementById("inputSuffix"),
            this.logosContainer = document.querySelector(".sdk-calculator__logo"),
            this.promotionalInstallments = document.getElementById("bmhub_PromotionalInstallments"),
            this.regularlInstallmentMin = document.getElementById("bmhub_RegularInstallmentMin"),
            this.regularlInstallmentMax = document.getElementById("bmhub_RegularInstallmentMax")
    }
    setInstallmentSuffix(t) {
        switch (t) {
            case 1:
                this.installmentNumberSuffix.innerHTML = "rata",
                    this.inputSuffix.innerHTML = "rata";
                break;
            case 2:
            case 3:
            case 4:
                this.installmentNumberSuffix.innerHTML = "raty",
                    this.inputSuffix.innerHTML = "raty";
                break;
            default:
                this.installmentNumberSuffix.innerHTML = "rat",
                    this.inputSuffix.innerHTML = "rat"
        }
    }
    setSliderInputPosition() {
        var t, e;
        this.activeCalculatorLabel && (e = this.sliderInput.getBoundingClientRect().width,
            t = this.sliderInputContainer.getBoundingClientRect(),
            e = this.activeCalculatorLabel.getBoundingClientRect().left - t.left - e / 2,
            this.sliderInput.style.left = e + "px",
            this.installmentInput.value === this.installmentInput.getAttribute("min") ? (this.buttonIncreaseValue.classList.remove("hidden"),
                this.buttonDecreaseValue.classList.add("hidden")) : this.installmentInput.value === this.installmentInput.getAttribute("max") ? (this.buttonDecreaseValue.classList.remove("hidden"),
                this.buttonIncreaseValue.classList.add("hidden")) : (this.buttonDecreaseValue.classList.remove("hidden"),
                this.buttonIncreaseValue.classList.remove("hidden")))
    }
    getInitialCalculatorData(e) {
        var t = {
            amount: e.creditValue,
            merchant_id: e.merchantId
        };
        const a = document.getElementById("bmhub_OpenCalculatorDialog")
            , l = document.querySelector(this.merchantData.paymentSelectorClass);
        fetch("https://bm-hub-test.bsbox.pl/integrator/initial_credit_calculation/?" + new URLSearchParams(t)).then(t=>(a && 200 === t.status && (a.style.display = "block"),
        l && 200 !== t.status && (l.style.display = "none"),
            t.json())).then(t=>t.months ? (a && (a.style.display = "block"),
            this.sdkCalculatorContainer.classList.add("display"),
            this.initialMonth = t.months,
            this.initialInstallment = t.installment,
            this.initialRRSO = t.rrso,
            this.installmentNumberSummary.innerHTML = t.months,
            this.installmentSummary.innerHTML = t.installment,
            this.rrsoSummary.innerHTML = t.rrso,
            this.totalCostSummary.innerHTML = t.total_cost,
            void this.getDetailedCalculatorData(e)) : (this.loader.style.display = "none",
            this.sdkCalculatorContainer.style.display = "block",
            this.sdkCalculatorContainer.innerHTML = "Brak dostępnych ofert ratalnych.",
            this.sdkCalculatorContainer.classList.add("sdk-calculator__loan-header"),
            this.sliderInputContainer.style.display = "none",
        a && (document.getElementById("sdk-openCalculatorDialog").remove(),
            document.getElementById("sdk-payment-available").innerHTML = "Dostępna płatność w dopasowanych do Ciebie ratach"),
            void (l && (l.style.display = "none")))).catch(t=>{
                l && (l.style.display = "none")
            }
        )
    }
    getDetailedCalculatorData(n) {
        this.rangeLabels.innerHTML = "",
            this.tenors = [];
        var t = {
            amount: n.creditValue,
            merchant_id: n.merchantId
        };
        fetch("https://bm-hub-test.bsbox.pl/integrator/detailed_credit_calculation/?" + new URLSearchParams(t)).then(t=>t.json()).then(t=>{
                if (this.token = t.token,
                    this.loader.style.display = "none",
                    this.sdkCalculatorContainer.style.display = "block",
                    t.credit_data) {
                    document.getElementById("bmhub_OpenCalculatorDialog") && (document.getElementById("bmhub_OpenCalculatorDialog").style.display = "block"),
                        this.tenors = t.credit_data.sort((t,e)=>t.installment_no - e.installment_no),
                        this.tenors.forEach((e,a)=>{
                                let l = document.createElement("li");
                                if (l.classList.add("sdk-calculator__label"),
                                    e.is_promotional) {
                                    let t = [];
                                    t[a] = e.installment_no,
                                        t = t.filter(t=>t),
                                        this.promotionalInstallments.innerHTML = t,
                                        l.classList.add("sdk-calculator__label--orange")
                                } else
                                    l.classList.add("sdk-calculator__label--primary");
                                this.rangeLabels.appendChild(l),
                                e.installment_no === this.initialMonth && (this.installmentSlider.value = String(a),
                                    this.rangeLabels.children[a].setAttribute("id", "activeCalculatorLabel"),
                                    this.activeCalculatorLabel = document.getElementById("activeCalculatorLabel"))
                            }
                        );
                    var e = this.tenors.length - 1;
                    this.installmentSlider.setAttribute("min", String(0)),
                        this.installmentSlider.setAttribute("max", String(e)),
                        this.installmentInput.setAttribute("min", this.tenors[0].installment_no),
                        this.installmentInput.setAttribute("max", this.tenors[e].installment_no),
                        document.getElementById("monthsMin").innerHTML = this.tenors[0].installment_no,
                        document.getElementById("monthsMax").innerHTML = this.tenors[e].installment_no,
                        this.regularlInstallmentMin.innerHTML = this.tenors[0].installment_no,
                        this.regularlInstallmentMax.innerHTML = this.tenors[e].installment_no,
                        this.installmentInput.value = String(this.initialMonth);
                    const a = document.getElementById("representativeExamplePromotional")
                        , l = document.getElementById("representativeExampleRegular");
                    a.innerHTML = t.representative_examples.promotional,
                        l.innerHTML = t.representative_examples.regular,
                    t.representative_examples.promotional || (a.style.display = "none",
                        document.querySelectorAll(".sdk-calculator__set-opacity").forEach(t=>{
                                t.classList.add("sdk-calculator__opacity")
                            }
                        )),
                        this.merchantData = {
                            merchantId: n.merchantId,
                            creditValue: n.creditValue,
                            paymentSelectorClass: n.paymentSelectorClass,
                            paymentPriceClass: n.paymentPriceClass,
                            productPriceClass: n.productPriceClass,
                            installmentNo: Number(this.installmentInput.value)
                        },
                        localStorage.setItem("sdk-merchant", JSON.stringify(this.merchantData)),
                        this.setInstallmentSuffix(this.initialMonth),
                        this.setSliderInputPosition()
                } else
                    console.error("There is no data")
            }
        )
    }
    sendCalculatorData(t) {
        t = {
            token: this.token,
            merchant_id: t.merchantId,
            installment_no: t.installmentNo
        };
        fetch("https://bm-hub-test.bsbox.pl/integrator/installment_selection/", {
            method: "POST",
            headers: {
                "Content-Type": "application/json;charset=utf-8"
            },
            body: JSON.stringify(t)
        }).then(t=>t).then(()=>{
                document.body.dispatchEvent(new CustomEvent("getSdkToken",{
                    detail: {
                        token: this.token
                    }
                }))
            }
        )
    }
    updateDetailedCalculatorData(t) {
        document.getElementById("activeCalculatorLabel").removeAttribute("id"),
            this.installmentSliderValue = this.installmentSlider.value,
            this.rangeLabels.children[this.installmentSliderValue].setAttribute("id", "activeCalculatorLabel"),
            this.activeCalculatorLabel = document.getElementById("activeCalculatorLabel"),
            this.tenors[this.installmentSliderValue].is_promotional ? document.querySelectorAll(".sdk-calculator__set-opacity").forEach(t=>{
                    t.classList.remove("sdk-calculator__opacity")
                }
            ) : document.querySelectorAll(".sdk-calculator__set-opacity").forEach(t=>{
                    t.classList.add("sdk-calculator__opacity")
                }
            ),
            this.installmentInput.value = this.tenors[this.installmentSliderValue].installment_no,
            this.installmentNumberSummary.innerHTML = this.tenors[this.installmentSliderValue].installment_no,
            this.installmentSummary.innerHTML = this.tenors[this.installmentSliderValue].installment,
            this.rrsoSummary.innerHTML = this.tenors[this.installmentSliderValue].rrso,
            this.totalCostSummary.innerHTML = this.tenors[this.installmentSliderValue].total_cost,
            this.merchantData = {
                merchantId: t.merchantId,
                creditValue: t.creditValue,
                paymentSelectorClass: t.paymentSelectorClass,
                paymentPriceClass: t.paymentPriceClass,
                productPriceClass: t.productPriceClass,
                installmentNo: Number(this.installmentInput.value)
            },
            localStorage.setItem("sdk-merchant", JSON.stringify(this.merchantData)),
            this.setInstallmentSuffix(this.tenors[this.installmentSliderValue].installment_no),
            this.setSliderInputPosition()
    }
    openModal() {
        const t = document.getElementById("sdk-openCalculatorDialog")
            , e = document.querySelector(this.merchantData.paymentSelectorClass);
        this.checkAndSetModalVariable(),
            t ? t.addEventListener("click", ()=>{
                    document.getElementById("calculatorModal").style.display = "block",
                        document.getElementById("calculatorModal").style.backgroundColor = "rgba(0,0,0,0.6)",
                        document.querySelector("body").style.overflow = "hidden",
                        this.setSliderInputPosition()
                }
            ) : e && e.addEventListener("click", ()=>{
                    document.getElementById("calculatorModal").style.display = "block",
                        document.getElementById("calculatorModal").style.backgroundColor = "rgba(0,0,0,0.6)",
                        document.querySelector("body").style.overflow = "hidden",
                        this.setSliderInputPosition()
                }
            )
    }
    closeModal() {
        document.querySelectorAll(".btn--close").forEach(t=>{
                t.addEventListener("click", ()=>{
                        document.getElementById("calculatorModal").style.display = "none",
                            document.querySelector("body").style.overflow = ""
                    }
                )
            }
        )
    }
    getLogoImages() {
        fetch("https://bm-hub-test.bsbox.pl/integrator/logotypes/").then(t=>t.json()).then(t=>{
                t.forEach(t=>{
                        let e = document.createElement("img");
                        e.setAttribute("src", t.imageData),
                            e.setAttribute("alt", t.displayName),
                            this.logosContainer.appendChild(e)
                    }
                );
                let e = document.createElement("small");
                e.innerText = "więcej banków wkrótce",
                    this.logosContainer.appendChild(e)
            }
        )
    }
    checkAndSetModalVariable() {
        const t = document.querySelector(this.merchantData.paymentSelectorClass);
        var e = document.querySelector(this.merchantData.paymentPriceClass);
        const a = document.getElementById("sendCalculatorData")
            , l = document.querySelector(".sdk-calculator__title-product")
            , n = document.querySelector(".sdk-calculator__title-checkout");
        if (!e && t)
            return a.style.display = "none",
                l.style.display = "block",
                n.style.display = "none",
                document.querySelector(".sdk-calculator__description-special").style.display = "block",
                void (document.querySelector(".sdk-calculator__description").style.display = "none");
        t ? (a.style.display = "block",
            l.style.display = "none",
            n.style.display = "block") : (a.style.display = "none",
            l.style.display = "block",
            n.style.display = "none",
        t && (t.style.display = "none"))
    }
    initialize() {
        if (document.getElementById("calculatorModal") && JSON.parse(localStorage.getItem("sdk-merchant"))) {
            this.merchantData = JSON.parse(localStorage.getItem("sdk-merchant"));
            const e = document.querySelector(this.merchantData.productPriceClass)
                , a = document.querySelector(this.merchantData.paymentPriceClass);
            var t;
            e ? (t = parseFloat(e.innerText.replace(/[^\d,.-]/g, "").replace(",", ".")),
                this.merchantData.creditValue = t,
                localStorage.setItem("sdk-merchant", JSON.stringify(this.merchantData))) : a ? (t = parseFloat(a.innerText.replace(/[^\d,.-]/g, "").replace(",", ".")),
                this.merchantData.creditValue = t,
                localStorage.setItem("sdk-merchant", JSON.stringify(this.merchantData))) : this.checkAndSetModalVariable(),
                this.getInitialCalculatorData(this.merchantData),
                this.installmentSlider.addEventListener("change", ()=>{
                        this.updateDetailedCalculatorData(this.merchantData)
                    }
                ),
                this.buttonIncreaseValue.addEventListener("click", ()=>{
                        this.installmentSlider.value = String(Number(this.installmentSlider.value) + 1),
                            this.updateDetailedCalculatorData(this.merchantData)
                    }
                ),
                this.buttonDecreaseValue.addEventListener("click", ()=>{
                        this.installmentSlider.value = String(Number(this.installmentSlider.value) - 1),
                            this.updateDetailedCalculatorData(this.merchantData)
                    }
                ),
                this.buttonSendCalculatorData.addEventListener("click", ()=>{
                        this.sendCalculatorData(this.merchantData),
                            document.getElementById("calculatorModal").style.display = "none",
                            document.querySelector("body").style.overflow = ""
                    }
                ),
                window.addEventListener("resize", ()=>{
                        this.setSliderInputPosition()
                    }
                ),
                this.getLogoImages(),
                this.openModal(),
                this.closeModal()
        }
    }
    initializeCalculator() {
        let t = document.createElement("div");
        t.setAttribute("id", "calculatorModal"),
            t.setAttribute("class", "sdk-modal sdk-modal--calculator");
        t.innerHTML = '    <div class="sdk-modal__dialog">        <div class="position-relative">            <i class="sdk-modal__icon btn--close">                <svg width="14" height="14" viewBox="0 0 14 14" fill="none" xmlns="http://www.w3.org/2000/svg">                    <path d="M13.3002 0.709971C12.9102 0.319971 12.2802 0.319971 11.8902 0.709971L7.00022 5.58997L2.11022 0.699971C1.72022 0.309971 1.09021 0.309971 0.700215 0.699971C0.310215 1.08997 0.310215 1.71997 0.700215 2.10997L5.59022 6.99997L0.700215 11.89C0.310215 12.28 0.310215 12.91 0.700215 13.3C1.09021 13.69 1.72022 13.69 2.11022 13.3L7.00022 8.40997L11.8902 13.3C12.2802 13.69 12.9102 13.69 13.3002 13.3C13.6902 12.91 13.6902 12.28 13.3002 11.89L8.41021 6.99997L13.3002 2.10997C13.6802 1.72997 13.6802 1.08997 13.3002 0.709971Z" fill="#3C3C46"/>                </svg>            </i>            <div class="sdk-modal__content">                <div class="sdk-calculator">                    <div class="sdk-calculator__title-product">                      <div class="sdk-calculator__title">Sprawdź, jakie raty oferujemy</div>                      <div class="sdk-calculator__description">                         Zapoznaj się z dostępnymi ofertami ratalnymi. Ostatecznego wyboru                          dokonasz po dodaniu wszystkich artykułów do koszyka.                      </div>                      <div class="sdk-calculator__description-special">                         Zapoznaj się z dostępnymi ofertami ratalnymi. Ostatecznego wyboru                          dokonasz po złożeniu zamówienia.                      </div>                    </div>                    <div class="sdk-calculator__title-checkout">                      <div class="sdk-calculator__title ">Wybierz raty dogodne dla siebie</div>                      <div class="sdk-calculator__description">                         Wypełniasz wniosek raz, a my składamy go w kolejnych bankach. Zwiększamy dla Ciebie szansę na otrzymanie rat.                      </div>                    </div>                    <div id="sdk-loading" class="sdk-loading"></div>                    <div id="sdk-calculator__loan-container" class="sdk-calculator__loan-container">                    <div class="sdk-calculator__header sdk-calculator__flex sdk-calculator__flex--align margin-b-30">                        <div class="sdk-calculator__loan">                            <div class="sdk-calculator__loan-header"><span id="installmentNumber"></span> <span id="installmentNumberSuffix">rat</span> x <span id="installment"></span> zł</div>                            <div class="sdk-calculator__loan-subheader">RRSO: <span id="rrso"></span>%, CAŁKOWITA KWOTA SPŁATY: <span id="totalCost"></span> zł</div>                        </div>                       <div>                          <button id="sendCalculatorData" type="button" class="sdk-button sdk-button--primary">Wybierz</button>                        </div>                                            </div>                    <div id="sliderInputContainer" class="sdk-calculator__slider-container margin-b-30">                        <div id="sliderInput" class="sdk-calculator__input-container">                            <div class="sdk-calculator__input">                                <input id="installmentInput"                                       type="number"                                       class="form-control form-control-input"                                />                                <small id="inputSuffix" class="input-sufix">rat</small>                            </div>                            <button type="button" class="sdk-calculator__button sdk-calculator__button--decrease">                                <svg width="16" height="16" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg">                                    <path d="M15 7.00003L3.83 7.00003L8.71 2.12003C9.1 1.73003 9.1 1.09003 8.71 0.700031C8.32 0.310031 7.69 0.310031 7.3 0.700031L0.709998 7.29003C0.319998 7.68003 0.319998 8.31003 0.709998 8.70003L7.29 15.3C7.68 15.69 8.31 15.69 8.7 15.3C9.09 14.91 9.09 14.28 8.7 13.89L3.83 9.00003L15 9.00003C15.55 9.00003 16 8.55003 16 8.00003C16 7.45003 15.55 7.00003 15 7.00003Z" fill="#00B5DD"/>                                </svg>                            </button>                            <button type="button" class="sdk-calculator__button sdk-calculator__button--increase">                                <svg width="16" height="16" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg">                                    <path d="M1 8.99997H12.17L7.29 13.88C6.9 14.27 6.9 14.91 7.29 15.3C7.68 15.69 8.31 15.69 8.7 15.3L15.29 8.70997C15.68 8.31997 15.68 7.68997 15.29 7.29997L8.71 0.699971C8.32 0.309971 7.69 0.309971 7.3 0.699971C6.91 1.08997 6.91 1.71997 7.3 2.10997L12.17 6.99997H1C0.45 6.99997 0 7.44997 0 7.99997C0 8.54997 0.45 8.99997 1 8.99997Z" fill="#00B5DD"/>                                </svg>                            </button>                        </div>                        <div class="sdk-calculator__range-slider margin-b-10">                            <input id="installmentSlider"                                   class="sdk-calculator__range"                                   type="range"                                   step="1"                                   min=""                                   max=""/>                            <ul id="rangeLabels" class="sdk-calculator__range-labels"></ul>                        </div>                        <div class="sdk-calculator__flex sdk-calculator__flex--between">                            <div class="sdk-calculator__data"><span id="monthsMin">2</span> raty</div>                            <div class="sdk-calculator__data sdk-calculator__data--second"><span id="monthsMax">60</span> rat</div>                        </div>                    </div>                    <div class="sdk-calculator__flex sdk-calculator__representative-example margin-b-30">                        <div class="sdk-calculator__flex sdk-calculator__flex-promotional margin-r-20">                            <div class="sdk-calculator__legend sdk-calculator__legend--orange sdk-calculator sdk-calculator__set-opacity"></div>                            <div>                                <div class="sdk-calculator__set-opacity"><strong>Promocyjne</strong> <span id="bmhub_PromotionalInstallments"></span> rat 0%</div>                                <span class="sdk-calculator__link sdk-calculator__tooltip"><span class="sdk-calculator__set-opacity">Przykład reprezentatywny</span>                                  <span id="representativeExamplePromotional" class="sdk-calculator__tooltip-text"></span>                                </span>                            </div>                        </div>                        <div class="sdk-calculator__flex">                            <div class="sdk-calculator__flex">                                <div class="sdk-calculator__legend sdk-calculator__legend--primary"></div>                                <div>                                    <div><strong>Raty regularne</strong> od <span id="bmhub_RegularInstallmentMin">2</span> do <span id="bmhub_RegularInstallmentMax">60</span> rat</div>                                    <span class="sdk-calculator__link sdk-calculator__tooltip">Przykład reprezentatywny                                  <span id="representativeExampleRegular" class="sdk-calculator__tooltip-text"></span>                                </span>                                </div>                            </div>                        </div>                    </div>                    </div>                    <div class="sdk-calculator__description">                        Możliwości finansowania zależą od zawartości Twojego koszyka - liczby oraz wartości produktów.<br>                        Skompletuj swoje zamówienie i sprawdź dostępne raty w koszyku.                    </div>                    <hr>                    <div class="sdk-calculator__description sdk-calculator__description-smaller">                        BLUE MEDIA jako pośrednik kredytowy pomaga w uzyskaniu najlepszych rat w Twojej sytuacji finansowej przy współpracy z bankami. Wypełniasz tylko jeden wniosek, a my pytamy o raty dla Ciebie w kolejnych bankach.                    </div>                    <div class="sdk-calculator__description sdk-calculator__description-smaller">Informacja o pośredniku kredytowym <a class="sdk-calculator__link" target="_blank" href="https://bluemedia.pl/rozwiazania/hub-ratalny">WIĘCEJ</a></div>                     <div class="sdk-calculator__flex sdk-calculator__logo"></div>                    <div class="sdk-calculator__footer">                        Przedstawione treści nie stanowią oferty w rozumieniu art. 66 § 1 Kodeksu cywilnego.                        Parametry na kalkulatorze są aktualne na moment ich prezentacji w serwisie według podanych                        założeń. Ostateczne warunki oferty zostaną zaprezentowane Klientowi przez Bank i mogą                        nieznacznie różnić się od parametrów widocznych w tym miejscu.                    </div>                    <div class="sdk-calculator__link btn--close">Wróć do poprzedniej strony</div>                </div>            </div>        </div></div>',
            document.body.appendChild(t);
        const e = document.getElementById("bmhub_OpenCalculatorDialog");
        e && (e.innerHTML = '<svg class="sdk-svg" width="20" height="17" viewBox="0 0 14 11" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M4.75012 8.12738L1.62262 4.99988L0.557617 6.05738L4.75012 10.2499L13.7501 1.24988L12.6926 0.192383L4.75012 8.12738Z" fill="#95C12B"></path></svg><span id="sdk-payment-available">Dostępna płatność w ratach</span><span id="sdk-openCalculatorDialog">SPRAWDŹ DOSTĘPNE RATY</span><p class="sdk-footer"><a href="https://pomoc.bluemedia.pl/platnosci-online-w-e-commerce/dopasowane-raty" target="_blank">Zobacz, w jaki sposób pomożemy Ci uzyskać najlepsze raty w Twojej sytuacji finansowej</a></p>')
    }
}
const sdk = new SDK;
if (JSON.parse(localStorage.getItem("sdk-merchant")))
    sdk.initializeCalculator(),
        sdk.getVariables(),
        sdk.initialize();
else {
    const fa = setInterval(()=>{
            JSON.parse(localStorage.getItem("sdk-merchant")) && (
                sdk.initializeCalculator(),
                sdk.getVariables(),
                sdk.initialize(),
                clearInterval(fa))
        }
        , 500)
}
