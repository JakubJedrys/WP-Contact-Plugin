# WP Contact Plugin

Lekka wtyczka WordPress dodająca dolną belkę kontaktową z szybkim dostępem do WhatsApp, telefonu i e-maila. Belka pojawia się wyłącznie na froncie strony i może być ograniczona do wybranych urządzeń.

## Funkcje
- Stała belka na dole ekranu z przełącznikiem menu i ikonami akcji.
- Łatwe ustawienia w panelu: numer telefonu (tel:), numer WhatsApp (wa.me), adres e-mail (mailto:), kolor belki/ikon.
- Opcje widoczności: wszędzie, tylko mobile, tylko desktop.
- Wybór położenia belki: prawa lub lewa strona ekranu.
- Obsługa ARIA, trybu prefer-reduced-motion i bezpiecznego paddingu dla wycięć ekranu.

## Instalacja
1. Skopiuj folder wtyczki do katalogu `wp-content/plugins/`.
2. Aktywuj „WP Contact Plugin” w panelu WordPress → Wtyczki.
3. Przejdź do „Ustawienia → Kontakt – belka”, uzupełnij dane kontaktowe i zapisz.

## Dostosowanie
- **Kolor**: ustaw w polu „Kolor belki i ikon”.
- **Widoczność**: wybierz tryb „Wszędzie”, „Tylko mobile” lub „Tylko desktop”.
- **Położenie**: wybierz „Prawa strona” lub „Lewa strona”, aby ustawić, gdzie pojawi się przycisk menu.

## Wymagania
- WordPress 5.8+
- PHP 7.4+

## Development
- Assets są ładowane tylko na froncie, gdy podany jest co najmniej jeden kanał kontaktu.
- Do testów składni można użyć `php -l wp-contact-plugin.php`.
