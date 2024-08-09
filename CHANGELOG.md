# Co nowego w BluePayment?

## Wersja 2.22.10
- Poprawienie literówki w config.xml

## Wersja 2.22.9
- Usunęliśmy nadpisanie place-order.js (zgłoszenie #12).

## Wersja 2.22.8
- Dodaliśmy wsparcie dla modułu Amasty One Step Checkout.
- Dodaliśmy opcję ustawienia linku do odpowiedniego kalkulatora (0 lub 1%) dla "Alior Raty".
- Poprawiliśmy działanie Google Pay.

## Wersja 2.22.7
- Dodaliśmy wsparcie dla Magento 2.4.7.
- Naprawiliśmy błąd z CSP dla Analytics.
- Naprawiliśmy błąd, jeśli adres URL logo jest pusty w odpowiedzi na listę bramek.
- Zaktualizowaliśmy treści dla płatności BLIK Płacę Później.

## Wersja 2.22.6
- Dodaliśmy możliwość dołączenia numeru telefonu dla startu płatności.
- Dodaliśmy nowe ustawienia (dot. zdjęcia) do białej listy CSP.
- Poprawiliśmy błąd z wielokrotną wysyłką e-maila przy płatności Google Pay / BLIK 0 / Kartą.
- Naprawiliśmy błąd z nieprawidłowym przekierowaniem, gdy opcja "Dodaj kod sklepu do adresu URL" była ustawiona na wartość true (dzięki @piotrmatras).

## Wersja 2.22.5
- Poprawiliśmy wsparcie dla Magneto 2.4.6 (zmiana Zend -> Laminas).
- Poprawiliśmy problem z wyścigiem statusów.

## Wersja 2.22.4
- Od teraz skrypty JS nie są dołączane w przypadku wyłączonej metody płatności Autopay.
- Dodaliśmy tłumaczenie kanałów płatności w szczegółach zamówienia.
- Usunęliśmy metodę Pay Smartney.

## Wersja 2.22.3
- Zmieniliśmy tekst pomocniczy dla BLIK Płacę Później.

## Wersja 2.22.2
- Poprawiliśmy literówką w tłumaczeniach.

## Wersja 2.22.1
- Zmieniliśmy tekst pomocy dla płatności Alior Raty.
- Zmieniliśmy tekst informacji o prowizji w panelu administracyjnym.
- Zmieniliśmy link do oferty regulaminów w panelu administracyjnym.
- Dodaliśmy metodę płatności "BLIK Zapłać później".
- Poprawiliśmy działanie w przypadku wystąpienia wyścigu statusów ITN.
- Poprawiliśmy błąd, który powodował brak widoczności metody Autopay w przypadku dostępnych tylko oddzielnych metod płatności.

## Wersja 2.22.0
- Dodaliśmy możliwość ustawienia metody płatności powyżej metody zbiorczej "Autopay" (płatność przelewem).
- Dokonaliśmy refaktoryzacji kodu front-end (JS) dla wszystkich kanałów Autopay.
- Zmieniliśmy swoją nazwę na Autopay.

## Wersja 2.21.7
- Dodaliśmy metodę płatności **Spingo** - odroczone płatności dla firm.

## Wersja 2.21.6
- Poprawiliśmy błąd podczas tworzenia transakcji **Google Pay**.
- Dodaliśmy stylowanie checkoutu dla modułu Mageplaza One Step Checkout.

## Wersja 2.21.5
- Poprawiliśmy wyświetlanie metody płatności w przypadku gdy dostępna jest tylko płatność kartą.

## Wersja 2.21.4
- Dodaliśmy nową metodę płatności **Visa Mobile**.
- Dodaliśmy informację o środowisku testowym w panelu administracyjnym.
- Zaktualizowaliśmy opis metody **PayPo**.
- Poprawiliśmy błąd z niepoprawnym użyciem metody `array_contains` zamiast `in_array` - [GitHub #8](https://github.com/bluepayment-plugin/magento-2.x-plugin/issues/8).
- Poprawiliśmy błąd z brakiem komentarza o zwrocie do szczegółów zamówienia w przypadku opcji **-- Nie zmieniaj statusu--**.
- Ustandaryzowaliśmy tekst **Klucz konfiguracyjny (hash)** (wcześniej _Klucz współdzielony_) w konfiguracji modułu.
- Dodaliśmy informację o aktualnej wersji platformy oraz moduły podczas rozpoczęcia transakcji.

## Wersja 2.21.3
- Dodaliśmy opcji "Wyłącz link kontynuacji płatności wraz z wygaśnięciem transakcji".
- Poprawiliśmy działanie analityki GA4 - gdy jest wyłączona, nie są już pobierane informacje o produktach.
- Poprawiliśmy pobieranie informacji o kategorii produktu w analityce GA4.

## Wersja 2.21.2
- Poprawiliśmy integrację z BluePaymentGraphQl.
- Poprawiliśmy błąd z blokiem ConsumerFinance.
- Poprawiliśmy błędy związane z dostawą na wiele adresów (multishipping).
- Poprawiliśmy błąd związany z ustawieniem statusu oraz state dla zamówień.
- Usunęliśmy metodę **initialize** (dodaną w wersji 2.20.0).
- Przenieśliśmy logikę ustawiania domyślnego status zamówienia (z wersji 2.20.0) do zewnętrznego observera.
- Poprawiliśmy działanie multishipping.

## Wersja 2.21.1
- Poprawiliśmy wsparcie dla Magento 2.3.*.

## Wersja 2.21.0
- Dodaliśmy walutę SEK.
- Dodaliśmy tekst z informacją dla metody płatności Autopay.
- Zmieniliśmy wygląd ekranu wyboru płatności.
- Dodaliśmy teksty pomocnicze w konfiguracji modułu oraz konfiguracji kanałów.
- Dodaliśmy nowy kanał płatności - PayPo.
- Od teraz także "Płatność kartą" oraz "PayPo" są zawsze wyświetlane jako osobne metody płatności.
- Poprawiliśmy obsługę wielu serwisów Autopay w ramach różnych kontekstów Magento.

## Wersja 2.20.0
- Dodaliśmy metodę **initialize** do klasy **BluePayment\Model\Method\BluePayment**, która ustawia domyślny status zamówienia, zgodnie z ustawieniem "Status waiting payment" w konfiguracji modułu (tylko dla zamówień złożonych z wykorzystaniem metody płatności BlueMedia).
- Zapewniliśmy wsparcie dla Magneto 2.4.4 oraz PHP 8.1.
- Dodaliśmy promowanie płatności Consumer Finance.
- Poprawiliśmy wygląd wyboru płatności.

## Wersja 2.19.2
- Wydzieliliśmy wyświetlanie zgód do osobnego widoku knockout.
- Dodaliśmy host `platnosci-accept.bm.pl` do CSP.
- Poprawiliśmy błąd podczas tworzenia płatności Google Pay.

## Wersja 2.19.1
- Bugfix — usunęliśmy błędny komponent w default.xml.

## Wersja 2.19.0
- Dodaliśmy rozszerzoną analitykę Google Analytics 4.
- Poprawiliśmy wyliczanie kwoty zamówienia przy wybraniu innej waluty.
- Dodaliśmy wywyoływanie eventów `bluemedia_payment_failure`, `bluemedia_payment_pending` oraz `bluemedia_payment_success` po otrzymaniu nowego statusu płatności.

## Wersja 2.18.0
- Zaktualizowaliśmy obsługę synchronizacji kanałów płatniczych do wersji v2.
- Poprawiliśmy zachowanie podczas obsługi powiadomień o płatnościach (race-condition).
- Zmieniliśmy nazwę kanału płatności.

## Wersja 2.17.1
- Poprawiliśmy sortowanie kanałów i metod płatności (tylko dla multishipping oraz GraphQL)

## Wersja 2.17.0
- Dodaliśmy opcję konfiguracyjną włączenia/wyłączenia **BLIK 0**.
- Dodaliśmy link do kontynuacji płatności w detalu zamówienia oraz mailu z podziękowaniem za zakupy.

## Wersja 2.16.0
- Dodaliśmy obsługę zgód formalnych.
- Zmieniliśmy endpoint pobierania kanałów płatności na `gatewayList` (REST API).
- Poprawiliśmy błąd z podwójnym zapisem transakcji przy ITNie.
- Dodaliśmy opcję **Nie zmieniaj statusu** przy ustawieniach statusów dla zwrotów.
- Zaktualizowaliśmy Instrukcję użytkownika oraz README.md.
- Zaktualizowaliśmy logo BlueMedia.
- Ukryliśmy metodę BlueMedia, kiedy żaden kanał płatności nie jest dostępny (lub wszystkie dostępne są ustawione jako oddzielna metoda płatności).

## Wersja 2.15.0
- Dodaliśmy obsługę „Dostawa na wiele adresów (multishipping)”.
- Zmieniliśmy scope konfiguracji z SCOPE_WEBSITE na SCOPE_STORE.

### Wersja 2.14.6
- Poprawiliśmy wybór kanału płatności.

## Wersja 2.14.2
- Zmieniliśmy kwoty dla "Smartney – Kup teraz, zapłać później" - z przedziału ~~100 zł - 2000 zł~~ na **100 zł - 2500 zł**.
- Dodaliśmy parametr "Language" do rozpoczęcia transakcji - zgodny z językiem sklepu danego zamówienia.

## Wersja 2.14.1
- Zmieniliśmy kwoty dla "Smartney – Kup teraz, zapłać później" - z przedziału ~~200 zł - 1500 zł~~ na **100 zł - 2000 zł**.

## Wersja 2.14.0
- Dodaliśmy opcję „Pokaż kanały płatności w sklepie” – domyślnie włączona.
- Poprawiliśmy opcję „Czas ważności płatności”

## Wersja 2.13.7
- Poprawiliśmy zachowania modułu przy wyłączonym Google Pay.

## Wersja 2.13.6
- Odblokowaliśmy Google Pay dla wszystkich walut.

## Wersja 2.13.5
- Poprawiliśmy składanie zamówienia – metoda order zamiast authorize.
- Poprawiliśmy db_schema.
- Zaktualizowaliśmy Content Security Policy.
- Zaktualizowaliśmy composer.json.

## Wersja 2.13.4
- Poprawiliśmy składanie zamówienia – metoda **order** zamiast **authorize**.

## Wersja 2.13.3
- Poprawiliśmy moduł konfiguracji kanałów płatności.
- Poprawiliśmy synchronizację kanałów dla wielu witryn.
- Wyłączyliśmy klikalny overlay dla BLIK 0 i Google Pay.

## Wersja 2.13.2
- Poprawiliśmy wyświetlanie niestandardowego logo BLIKa 0.

## Wersja 2.13.1
- Poprawiliśmy niestandardowe walidatory (additional-validators) w placeOrder.

## Wersja 2.13.0
- Dodaliśmy nową metodę płatności: Pay Smartney – Kup teraz, zapłać później (czyli płatności odroczone). Usługa jest dostępna dla transakcji realizowanych w polskiej walucie.

## Wersja 2.12.0
- Dodaliśmy możliwość zlecania zwrotów online poprzez faktury korygujące (Credit Memo).

## Wersja 2.11.0
- Dodaliśmy stronę oczekiwania na przekierowanie do płatności.

## Wersja 2.10.0
- Dodaliśmy opcję wysłania linku do płatności dla zamówień utworzonych z poziomu panelu administracyjnego.
- Wyłączyliśmy niepotrzebne zapytania do pay.google.com.
- Dodaliśmy tekst informacyjny przy kanale Apple Pay.
- Zmieniliśmy ścieżki tworzenia logów na `var/log/BlueMedia/Bluemedia-[data].log`
- Dodaliśmy informację o metodzie płatności do tabeli listy zamówień w panelu administracyjnym.
- Dodaliśmy zmienną payment_channel zawierającej nazwę kanału płatności do szablonów maila.

## Wersja 2.9.0
- Dodaliśmy Rozwijalną listę kanałów.
- Ukryliśmy nazwę kanału płatności na kroku płatności.

## Wersja 2.8.2
- Wprowadziliśmy zmiany w module zwrotów oraz na stronie powrotu z płatności
- Dodaliśmy obsługę walut: RON, HUF, BGN, UAH.
- Zmiany na stronie powrotu z płatności.

## Wersja 2.8.1
- Dostosowaliśmy moduł do wymagań Magento Marketplace.

## Wersja 2.8.0
- Dostosowaliśmy moduł do wymagań Magento Marketplace.

## Wersja 2.7.7
- Poprawiliśmy błąd, który czasem powodował niewyświetlenie okna BLIK 0 po wpisaniu kodu.

## Wersja 2.7.6
- Dodaliśmy obsługę nowej waluty: CZK.

## Wersja 2.7.5
- Wyświetlanie wszystkich dostępnych statusów w konfiguracji modułu.

## Wersja 2.7.4
- Dostosowaliśmy moduł płatności do wymagań Google Pay API 2.0..
- Uprościliśmy konfiguracji Google Pay.

## Wersja 2.7.3
- Zmieniliśmy mechanizm sortowania.
- Poprawiliśmy błąd związany z brakiem przekierowania na płatność Autopay.

## Wersja 2.7.2
- Dostosowaliśmy moduł do wymagań Magento Marketplace.
- Wraz z tą wersją zmieniła się struktura pliku .zip oraz komenda do instalacji i aktualizacji!

## Wersja 2.7.1
- Dodaliśmy wsparcie dla Magento 2.3.1

## Wersja 2.7.0
- Dodaliśmy płatności automatyczne

## Wersja 2.6.0
- Dodaliśmy wsparcie dla Magento 2.3.0
- Dodaliśmy bezpośrednią płatność przez Google Pay.

## Wersja 2.4.0
- Dodaliśmy obsługę walut: GBP, EUR, USD.

## Wersja 2.3.0
- Dodaliśmy obsługę zwrotów.
