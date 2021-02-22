# Instrukcja modułu „BluePayment” dla platformy Magento 2

## Podstawowe informacje
Moduł płatności umożliwiający realizację transakcji bezgotówkowych w sklepie Magento.

### Główne funkcje
- Obsługa wielu sklepów.
- Obsługa zakupów bez rejestracji.
- Obsługa dwóch trybów działania (dla każdego z poniższych trybów wymagane są̨ osobne dane kont, które należy uzyskać́ od Blue Media S.A.):
  - testowy,
  - produkcyjny.
- Realizacja dwóch sposobów wyświetlenia form płatności w sklepie:
  - Na stronie Blue Media – widok klasyczny lub spersonalizowany (po uzgodnieniu z Blue Media),
  - Na stronie sklepu – zintegrowany, Klient przenoszony jest od razu do banku lub na stronę płatności kartą.

### Wymagania
- Wersja Magento: 2.4.0 – 2.4.1.
- Wersja PHP zgodna z wymaganiami względem danej wersji sklepu.

## Opis zmian

### Wersja 2.13.6
- Odblokowanie Google Pay dla wszystkich walut.

### Wersja 2.13.5
- Poprawiono składanie zamówienia – metoda order zamiast authorize.
- Poprawiono db_schema.
- Zaktualizowano Content Security Policy.
- Zaktualizowano composer.json.

### Wersja 2.13.4
- Poprawiono składanie zamówienia – metoda order zamiast authorize.

### Wersja 2.13.3
- Poprawiono moduł konfiguracji kanałów płatności.
- Poprawiono synchronizację kanałów dla wielu witryn.
- Wyłączono klikalny overlay dla BLIK 0 i Google Pay.

### Wersja 2.13.2
- Poprawiono wyświetlanie niestandardowego logo BLIKa 0.

### Wersja 2.13.1
- Poprawiono niestandardowe walidatory (additional-validators) w placeOrder.

### Wersja 2.13.0
- Dodano Smartney – Kup teraz, zapłać później.

### Wersja 2.12.0
- Dodano zwroty on-line poprzez Faktury korygujące (Credit Memo).

### Wersja 2.11.0
- Dodano stronę oczekiwania na przekierowanie do płatności.

### Wersja 2.10.0
- Dodano opcję wysłania linku do płatności dla zamówień utworzonych z poziomu panelu administracyjnego.
- Wyłączono niepotrzebne zapytania do pay.google.com.
- Dodano tekst informacyjny przy kanale Apple Pay.
- Zmieniono ścieżki tworzenia logów na `var/log/BlueMedia/Bluemedia-[data].log`
- Dodano informację o metodzie płatności do tabeli listy zamówień w panelu administracyjnym.
- Dodano zmienną payment_channel zawierającej nazwę kanału płatności do szablonów maila.

### Wersja 2.9.0
- Dodano Rozwijalną listę kanałów.
- Ukryto nazwę kanału płatności na kroku płatności.

### Wersja 2.8.2
- Zmiany w module zwrotów.
- Dodano obsługę walut: RON, HUF, BGN, UAH.
- Zaktualizowano instrukcji dot. zwrotów.
- Zmiany na stronie powrotu z płatności.

### Wersja 2.8.1
- Dostosowanie wersji do Magento Marketplace.

### Wersja 2.8.0
- Dostosowanie wersji do Magento Marketplace.

### Wersja 2.7.7
- Poprawiono błąd, który czasem powodował niewyświetlenie okna BLIK 0 po wpisaniu kodu.

### Wersja 2.7.6
- Dodano obsługę waluty CZK.

### Wersja 2.7.5
- Wyświetlanie wszystkich dostępnych statusów w konfiguracji modułu.

### Wersja 2.7.4
- Dostosowanie do Google Pay API 2.0.
- Uproszczenie konfiguracji Google Pay.

### Wersja 2.7.3
- Zmiany w mechanizmie sortowania.
- Poprawka dot. braku przekierowania na płatność BM.

### Wersja 2.7.2
- Dostosowano do wymagań Marketplace Magento.
- Uwaga, z tą wersją zmieniła się struktura pliku .zip oraz komenda do instalacji i aktualizacji!

### Wersja 2.7.1
- Dodano wsparcie dla Magento 2.3.1

### Wersja 2.7.0
- Dodano płatności automatyczne

### Wersja 2.6.0
- Dodano wsparcie dla Magento 2.3.0
- Dodano bezpośrednią płatność przez Google Pay.

### Wersja 2.4.0
- Dodano obsługę walut: GBP, EUR, USD.

### Wersja 2.3.0
- Dodano obsługę zwrotów.

## Instalacja

### Poprzez composera
1. Wykonać komendę:
```
composer require bluemedia/module-bluepayment
```
2. Przejść do aktywacji modułu

### Poprzez paczkę .zip
1. Pobrać najnowszą wersję modułu ze strony http://s.bm.pl/wtyczki.
2. Wgrać plik .zip do katalogu głównego Magento.
3. Będąc w katalogu głównym Magento, wykonać komendę:
```bash
unzip -o -d app/code/BlueMedia/BluePayment bm-bluepayment-*.zip && rm bm-bluepayment-*.zip
```
4. Przejść do aktywacji modułu.


## Aktywacja modułu

### Aktywacja poprzez Panel Administracyjny
1.	Zalogować się do panelu administracyjnego.
2.	Zalogować się do panelu administracyjnego Magento.
3.	Z menu głównego wybrać **System** -> **Web Setup Wizard**. System poprosi o ponowne zalogowanie się.
4.	Przejść do **Component Manager**, na liście znaleźć moduł **BlueMedia/BluePayment**, kliknąć **Select** i następnie **Enable**.
5.	Kliknąć **Start Readiness Check**. Zostanie wykonana weryfikacja zależności. Następnie kliknąć **Next**.
6.	(opcjonalne) Utworzyć kopię zapasową, zaznaczając wszystkie opcje i klikając **Create Backup**.
7.	Po utworzeniu Backupu lub odznaczeniu opcji, przejść dalej klikając **Next**.
8.	Kliknąć **Enable**. Sklep zostanie wyłączony na czas aktywacji modułu.
9.	Aktywacja modułu może potrwać kilka minut. Po poprawnej aktywacji pojawi się komunikat


### Aktywacja poprzez linię poleceń
1. Będąc w katalogu głównym Magento, wykonać polecenia:
- ```bin/magento module:enable BlueMedia_BluePayment --clear-static-content```
- ```bin/magento setup:upgrade```
- ```bin/magento setup:di:compile```
- ```bin/magento cache:flush```
2. Moduł został aktywowany. Można przejść do Konfiguracji.

## Konfiguracja
Dostępna jest po zalogowaniu do panelu administracyjnego, wybierając w menu **Sklepy (Stores)** -> **Konfiguracja (Configuration)**, w kolejnym menu **Sprzedaż (Sales)** -> **Metody płatności (Payment Methods)** i następnie rozwinąć **OTHER PAYMENT METHODS** oraz **Płatność online BM (Online Payment BM)**.

### Podstawowa konfiguracja modułu
1. Przejść do strony konfiguracji modułu.
2. Wypełnić obowiązkowe pola:
    1. **Włączony (Enabled)** na **Tak (Yes)**.
    1. **Tytuł (Title)** – nazwa metody płatności widoczna dla klientów. 
    1. **Tryb testowy (Test Mode)**
3. Dla obsługiwanych walut wypełnić w zakładkach poniżej dane otrzymane od BM:
    1. **ID serwisu (Service partner ID)**.
    1. **Klucz współdzielony (Shared Key)** - serwisu dostarczony partnerowi przez BM.
4. Odświeżyć pamięć podręczną.

### Kanały płatności
Dostępne są po zalogowaniu do panelu administracyjnego, wybierając w menu po lewej stronie BluePayment -> **Kanały płatności (Gatways)**.

#### Odświeżenie listy kanałów płatności
Moduł automatycznie odświeża kanały płatności co 5 minut. Wymaga to skonfigurowania CRON-a, zgodnie z dokumentacją Magento: https://devdocs.magento.com/guides/v2.4/config-guide/cli/config-cli-subcommands-cron.html.

1. Przejść do listy kanałów płatności.
2. Kliknąć **Synchronizuj kanały płatności (Synchronize Gateways)** po prawej stronie ekranu.

#### Edycja kanałów płatności
1. Przejść do listy kanałów płatności.
2. Na liście kliknąć na wiersz z kanałem, który chcemy zedytować.
3. Dostępne opcje:
    1. **Status kanału (Status)** – Określa czy kanał jest aktualnie dostępny. Odświeżenie kanałów następuje co 5 minut (w przypadku poprawnego skonfigurowania CRONa).
    1. (informacyjnie) **Waluta (Currency)**
    1. (informacyjnie) **ID**
    1. (informacyjnie) **Nazwa banku (Bank Name)**
    1. (informacyjnie) **Nazwa (Name)** – Nazwa kanału.
    1. **Opis (Description)** – Opis wyświetlany klientowi pod nazwą kanału.
    1. **Kolejność (Sort Order)** – Kolejność sortowania na liście kanałów, gdzie:
        1 – pierwsza pozycja na liście,
        2 – druga pozycja na liście,
        ...
       0 – ostatnia pozycja na liście.
    1. (informacyjnie) **Rodzaj (Type)**.
    1. **Traktuj jako oddzielną metodę płatności (Is separated method)** – powoduje wyświetlanie danego kanału jako osobnej metody płatności.
    1. (informacyjnie) **URL do logo (Logo URL)**.
    1. **Użyj własnego logo (Use Own Logo)** – umożliwia użycie własnego logo dla kanału płatności.
    1. **Ścieżka do logo (Logo Path)** – adres do własnego logo, widoczne będzie przy zaznaczeniu opcji **Użyj własnego logo (Use Own Logo)**.
    1. (informacyjnie) **Data ostatniego odświeżenia (Status Date)** – data i czas ostatniej aktualizacji danych dot. kanału.
    1. **Wymuś wyłączenie (Force Disable)** – umożliwia wyłączenie kanału płatności, bez względu na **Status kanału (Status)**.

### Rozwijalna lista kanałów
Dostępna od wersji 2.9.0. Domyślnie włączona

W celu wyświetlania zawsze pełnej listy kanałów:
1. Przejść do strony konfiguracji modułu.
2. Wypełnić pole **Zwijalna lista kanałów (Collapsible gateway list)** na **Wyłącz (Disabled)**.
3. Odświeżyć pamięć podręczną.

### Odświeżenie pamięci podręcznej (cache)
Po każdej edycji konfiguracji, należy odświeżyć pamięć podręczną.
1. Przejść do **System** -> **Pamięć podręczna (Cache Management)**.
2. Zaznaczyć **Configuration**.
3. Z menu rozwijanego wybrać **Odśwież (Refresh)**.
4. Kliknąć **Wyślij (Submit)**.

## Zwroty
Umożliwia zwrot pieniędzy bezpośrednio na rachunek klienta, z którego została nadana płatność. Moduł umożliwia dwie metody zwrotu – poprzez fakturę korygującą (Credit Memo on-line) oraz bezpośrednio z zamówienia.

### Zwrot – faktura korygująca
1. Przejść do szczegółów **Faktury (Invoice)** dla zamówienia.
2. W górnym menu nacisnąć **Faktura korygująca (Credit Memo)**.
3. Uzupełnić formularz, podając ilość przedmiotów do zwrotu, wysokość opłat.
4. Nacisnąć **Zwróć (Refund)**.

### Zwrot - bezpośredni
1. Przejść do konfiguracji modułu.
2. Ustawić opcję **Pokaż ręczny zwrot BM w szczegółach zamówienia (Show manual BM refund in order details)** na **Włącz (Enable)**. Od tego momentu ta opcja będzie dostępna dla wszystkich zakończonych zamówień opłaconych poprzez ten moduł.
3. Przejść do szczegółów zamówienia.
4. Jeżeli zamówienie zostało opłacone z wykorzystaniem metody płatności BM, w górnym menu powinien być widoczny przycisk Zwrot BM.
5. Po kliknięciu pojawi się okno z możliwością wykonania zwrotu całej kwoty lub zwrotu częściowego.
    1. W przypadku zwrotu częściowego podać kwotę zwrotu w formacie „000.00” (kropka jako separator dziesiętny).
6. Potwierdzić akcję przyciskiem OK.
7. Pojawi się komunikat z potwierdzeniem wykonania zwrotu lub informacją, z jakiego powodu zwrot się nie powiódł.
8. Informacja o zwrocie dostępna jest w:
    1. Komentarzach do zamówienia.
    1. Na liście transakcji

## Płatność w iframe
Umożliwia klientom płatność kartą płatniczą bez wychodzenia ze sklepu i opuszczania procesu zakupowego. Implementacja takiej formy płatności ze względu na wymogi związane z bezpieczeństwem procesowania transakcji wymaga dwóch dodatkowych dokumentów.

### Aktywacja
1. Przejść do konfiguracji modułu.
2. Ustawić opcję **Płatność w iFrame (Iframe Payment)** na **Włącz (Enable)**.
3. Przejść do edycji kanału o ID 1500 i nazwie banku Karty.
4. Ustawić opcję **Traktuj jako oddzielną metodę płatności (Is separated method)**.
5. Odświeżyć pamięć podręczną.

## BLIK 0
BLIK „wewnątrz sklepu”, w tym przypadku kod zabezpieczający podawany jest bezpośrednio na stronie sklepu w ostatnim etapie procesu zakupowego.

### Aktywacja
1. Przejść do edycji kanału o ID 509 i nazwie kanału BLIK.
2. Ustawić opcję **Traktuj jako oddzielną metodę płatności (Is separated method)**.
3. Odświeżyć pamięć podręczną.

## Google Pay
Umożliwia płatność z wykorzystaniem Google Pay, bezpośrednio na stronie sklepu, w ostatnim etapie procesu zakupowego. W celu aktywacji, skontaktuj się ze swoim doradcą.

### Aktywacja
Google Pay jest domyślnie aktowywany.
Kanał wyświetlany jest zawsze jako osobna metoda płatności.
Płatności automatyczne
Płatności jednym kliknięciem - One Click Payment to kolejny sposób na wygodne płatności z wykorzystaniem kart płatniczych. Pozwalają na realizowanie szybkich płatności, bez konieczności każdorazowego podawania przez klienta wszystkich danych uwierzytelniających kartę. Proces obsługi płatności polega na jednorazowej autoryzacji płatności kartą przez i przypisaniu danych karty do konkretnego klienta. Pierwsza transakcja zabezpieczona jest protokołem 3D-Secure, natomiast kolejne realizowane są na podstawie przesłanego przez partnera żądania obciążenia karty.
Płatność automatyczna dostępna jest tylko dla zalogowanych klientów.

Aktywacja
1. Przejść do konfiguracji modułu.
2. Wypełnić **Autopay Agreement** odpowiednim regulaminem do akceptacji przez klienta.
3. Przejść do edycji kanału o ID **1503** i rodzaju **Płatność automatyczna**.
4. Ustawić opcję **Traktuj jako oddzielną metodę płatności (Is separated method)**.
5. Odświeżyć pamięć podręczną.

### Zarządzanie kartami
Karta zostaje zapamiętana i powiązana z kontem klienta przy pierwszej poprawnej płatności z wykorzystaniem płatności automatycznej i zaakceptowaniu regulaminu.
Klient ma możliwość usunięcia zapamiętanej kart z poziomu swojego konta. W tym celu należy:
1. Zalogować się do sklepu.
2. Z górnego menu wybrać **Moje konto (My account)**.
3. Z menu użytkownika po lewej, wybrać **Zapisane karty płatnicze (Saved payment cards)**.
4. Wyświetli się lista zapisanych kart:
5. Kliknąć **Usuń** i potwierdzić.

## Generowanie zamówień z poziomu panelu administracyjnego
Moduł umożliwia wysłanie linka do płatności do klienta dla zamówień utworzonych bezpośrednio w panelu administracyjnym.
W tym celu należy przy tworzeniu zamówienia wybrać kanał płatności BM.

Link do płatności zostanie przesłany przez BM na adres mailowy podany przy danych klienta.

## Szablony e-mail
Dla wiadomości:
- email_creditmemo_set_template_vars_before
- email_invoice_set_template_vars_before
- email_order_set_template_vars_before
- email_shipment_set_template_vars_before
moduł rozszerza listę dostępnych zmiennych o payment_channel. Przykładowe użycie w szablonie:
`{{var payment_channel|raw}}`

## Strona oczekiwania na przekierowanie
Moduł umożliwia dodanie strony pośredniej, wyświetlanej przed samym przekierowaniem użytkownika do płatności. Może to być wykorzystane np. do śledzenia e-commerce w Google Analytics.
Szablon, który jest wykorzystywany: `view/frontend/templates/redirect.phtml`

### Aktywacja
1. Przejść do konfiguracji modułu.
2. Ustawić opcję **Pokaż stronę oczekiwania przed przekierowaniem (Show waiting page before redirect)** na **Włącz (Enable)**.
3. Ustawić opcję **Sekund oczekiwania przed przekierowaniem (Seconds to wait before redirect)** – w celu określenia jak długo strona ma być wyświetlana.
4. Odświeżyć pamięć podręczną

## Aktualizacja modułu

### Przez composera
1. Wykonać komendę
```bash
composer update bluemedia/module-bluepayment
bin/magento setup:upgrade
bin/magento setup:di:compile
bin/magento cache:flush
```

### Przez paczkę .zip
1. Pobrać najnowszą wersję modułu ze strony http://s.bm.pl/wtyczki.
2. Wgrać plik .zip do katalogu głównego Magento.
3. Będąc w katalogu głównym Magento, wykonać komendy:
```bash
unzip -o -d app/code/BlueMedia/BluePayment bm-bluepayment-*.zip && rm bm-bluepayment-*.zip
bin/magento setup:upgrade
bin/magento setup:di:compile
bin/magento cache:flush
```
4. Moduł został zaktualizowany.

## Dezinstalacja modułu

### Za pomocą Web Setup Wizard
1. Zalogować się do panelu administracyjnego Magento.
2. Z menu głównego wybrać **System** -> **Web Setup Wizard**. System poprosi o ponowne zalogowanie się.
3. Przejść do Component Manager, na liście znaleźć moduł **BlueMedia/BluePayment**, kliknąć **Select** i następnie **Disable**.
4. Kliknąć **Start Readiness Check**. Zostanie wykonana weryfikacja zależności. Następnie kliknąć **Next**.
5. (opcjonalne) Utworzyć kopię zapasową, zaznaczając wszystkie opcje i klikając **Create Backup**.
6. Po utworzeniu Backupu lub odznaczeniu opcji, przejść dalej klikając **Next**.
7. Kliknąć **Disable**. Sklep zostanie wyłączony na czas aktywacji modułu.
8. Deaktywacja modułu może potrwać kilka minut. Po poprawnej deaktywacji pojawi się komunikat.

### Poprzez linię poleceń
1. Będąc w katalogu głównym Magento, wykonać polecenia:
```bash
bin/magento module:disable BlueMedia_BluePayment --clear-static-content
bin/magento setup:upgrade
bin/magento setup:di:compile
bin/magento cache:flush
```

(opcjonalnie) Czyszczenie plików oraz bazy danych
2. Z katalogu głównego Magento usunąć katalog: `app/code/BlueMedia`
3. Wykonać następujące zapytania do bazy danych:
```sql
DROP TABLE blue_card;
DROP TABLE blue_gateway;
DROP TABLE blue_refund;
DROP TABLE blue_transaction;
```
4. W celu usunięcia całej konfiguracji modułu, należy wykonać następujące zapytanie do bazy danych:
```sql
DELETE FROM core_config_data WHERE path LIKE 'payment/bluepayment%';
```
