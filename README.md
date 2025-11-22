# Kontakt Dock

Lekka wtyczka WordPress dodająca dolną belkę wysuwaną do góry lub pływające koło kontaktu z szybkim dostępem do WhatsApp, telefonu i e-maila. Wtyczka pojawia się wyłącznie na froncie strony i może być ograniczona do wybranych urządzeń.

## Funkcje
- Stała belka na dole ekranu **lub** kompaktowe pływające koło doklejane do narożnika (również 0,0) z możliwością drag & drop, teraz z panelem rozsuwanym w górę.
- Własne SVG / klasy z biblioteki ikon, ikony zamknięte/otwarte, pulsowanie oraz animacja slide+fade panelu.
- Kolory globalne i per przycisk, kontrola rozmiaru (S/M/L), wyrównanie panelu do krawędzi, offsety X/Y + dodatkowy offset pod belkę cookies oraz iOS safe-area.
- Łatwe ustawienia w panelu: numery telefon/WhatsApp, adres e-mail, widoczność (wszędzie / mobile / desktop), pozycja lewa/prawa, pion góra/dół.
- Obsługa ARIA, trybu prefer-reduced-motion, bezpiecznego paddingu dla wycięć ekranu i zapisywania pozycji koła w localStorage.
- Wybór ikon: domyślne emoji, oficjalne znaki marek (WhatsApp, Facebook, Instagram), własne SVG lub klasy biblioteczne z zachowaniem brand guidelines (bez zmiany kolorów ani kształtów).

## Instalacja
1. Skopiuj folder wtyczki do katalogu `wp-content/plugins/`.
2. Aktywuj „Kontakt Dock” w panelu WordPress → Wtyczki.
3. Otwórz pozycję menu „Kontakt Dock” (główne menu po lewej), uzupełnij dane kontaktowe i zapisz.

## Dostosowanie
- **Układ**: wybierz „Dolna belka” lub „Pływające koło”.
- **Położenie**: lewa/prawa krawędź oraz góra/dół z ręcznymi offsetami X/Y i dodatkowym marginesem pod belkę cookies.
- **Kolory**: ustaw globalny kolor oraz indywidualne kolory przycisków WhatsApp/Telefon/E-mail.
- **Ikony i animacje**: wybierz domyślne ikony, oficjalne znaki marek (bez modyfikacji kolorów/kształtów), własny kod SVG lub klasy ikon, ustaw inne ikony otwarte/zamknięte, włącz pulsowanie i wybierz rozmiar przycisków.

## Wymagania
- WordPress 5.8+
- PHP 7.4+

## Development
- Assets są ładowane tylko na froncie, gdy podany jest co najmniej jeden kanał kontaktu.
- Do testów składni można użyć `php -l wp-contact-plugin.php`.
