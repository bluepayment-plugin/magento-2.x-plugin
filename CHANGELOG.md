# Changelog

## Wersja 2.16.0
- Dodano obsługę zgód formalnych.
- Zmiana endpointu pobierania kanałów płatności na `gatewayList` (REST API).
- Poprawienie podwójnego zapisu transakcji przy ITNie.
- Dodanie opcji "Nie zmieniaj statusu" przy ustawieniach statusów dla zwrotów.

## Wersja 2.15.0
- Dodano obsługę „Dostawa na wiele adresów (multishipping)”.
- Zmieniono scope konfiguracji z SCOPE_WEBSITE na SCOPE_STORE.

### Wersja 2.14.6
- Poprawiono wybór kanału płatności.

## Wersja 2.14.2
- Zmieniono kwoty dla "Smartney – Kup teraz, zapłać później" - z przedziału ~~100 zł - 2000 zł~~ na **100 zł - 2500 zł**.
- Dodanie parametru "Language" do rozpoczęcia transakcji - zgodnego z językiem sklepu danego zamówienia.

## Wersja 2.14.1
- Zmieniono kwoty dla "Smartney – Kup teraz, zapłać później" - z przedziału ~~200 zł - 1500 zł~~ na **100 zł - 2000 zł**.

## Wersja 2.14.0
- Dodano opcję „Pokaż kanały płatności w sklepie” – domyślnie włączona.
- Poprawiono opcję „Czas ważności płatności”

## Wersja 2.13.7
- Poprawienie zachowania modułu przy wyłączonym Google Pay.

## Wersja 2.13.6
- Odblokowanie Google Pay dla wszystkich walut.

## Wersja 2.13.5
- Poprawiono składanie zamówienia – metoda order zamiast authorize.
- Poprawiono db_schema.
- Zaktualizowano Content Security Policy.
- Zaktualizowano composer.json.

## Wersja 2.13.4
- Poprawiono składanie zamówienia – metoda order zamiast authorize.

## Wersja 2.13.3
- Poprawiono moduł konfiguracji kanałów płatności.
- Poprawiono synchronizację kanałów dla wielu witryn.
- Wyłączono klikalny overlay dla BLIK 0 i Google Pay.

## Wersja 2.13.2
- Poprawiono wyświetlanie niestandardowego logo BLIKa 0.

## Wersja 2.13.1
- Poprawiono niestandardowe walidatory (additional-validators) w placeOrder.

## Wersja 2.13.0
- Dodano Smartney – Kup teraz, zapłać później.

## Wersja 2.12.0
- Dodano zwroty on-line poprzez Faktury korygujące (Credit Memo).

## Wersja 2.11.0
- Dodano stronę oczekiwania na przekierowanie do płatności.

## Wersja 2.10.0
- Dodano opcję wysłania linku do płatności dla zamówień utworzonych z poziomu panelu administracyjnego.
- Wyłączono niepotrzebne zapytania do pay.google.com.
- Dodano tekst informacyjny przy kanale Apple Pay.
- Zmieniono ścieżki tworzenia logów na `var/log/BlueMedia/Bluemedia-[data].log`
- Dodano informację o metodzie płatności do tabeli listy zamówień w panelu administracyjnym.
- Dodano zmienną payment_channel zawierającej nazwę kanału płatności do szablonów maila.

## Wersja 2.9.0
- Dodano Rozwijalną listę kanałów.
- Ukryto nazwę kanału płatności na kroku płatności.

## Wersja 2.8.2
- Zmiany w module zwrotów.
- Dodano obsługę walut: RON, HUF, BGN, UAH.
- Zaktualizowano instrukcji dot. zwrotów.
- Zmiany na stronie powrotu z płatności.

## Wersja 2.8.1
- Dostosowanie wersji do Magento Marketplace.

## Wersja 2.8.0
- Dostosowanie wersji do Magento Marketplace.

## Wersja 2.7.7
- Poprawiono błąd, który czasem powodował niewyświetlenie okna BLIK 0 po wpisaniu kodu.

## Wersja 2.7.6
- Dodano obsługę waluty CZK.

## Wersja 2.7.5
- Wyświetlanie wszystkich dostępnych statusów w konfiguracji modułu.

## Wersja 2.7.4
- Dostosowanie do Google Pay API 2.0.
- Uproszczenie konfiguracji Google Pay.

## Wersja 2.7.3
- Zmiany w mechanizmie sortowania.
- Poprawka dot. braku przekierowania na płatność BM.

## Wersja 2.7.2
- Dostosowano do wymagań Marketplace Magento.
- Uwaga, z tą wersją zmieniła się struktura pliku .zip oraz komenda do instalacji i aktualizacji!

## Wersja 2.7.1
- Dodano wsparcie dla Magento 2.3.1

## Wersja 2.7.0
- Dodano płatności automatyczne

## Wersja 2.6.0
- Dodano wsparcie dla Magento 2.3.0
- Dodano bezpośrednią płatność przez Google Pay.

## Wersja 2.4.0
- Dodano obsługę walut: GBP, EUR, USD.

## Wersja 2.3.0
- Dodano obsługę zwrotów.
