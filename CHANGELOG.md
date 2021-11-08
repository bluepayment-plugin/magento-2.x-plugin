# Co nowego w BluePayment?

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
- Poprawiliśmy błąd związany z brakiem przekierowania na płatność BM.

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
