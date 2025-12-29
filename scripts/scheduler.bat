@echo off
:: Site Takip Otomatik Gorev Tetikleyici
:: Bu dosyayi Windows Gorev Zamanlayicisi (Task Scheduler) ile
:: her 5 dakikada bir calisacak sekilde ayarlayabilirsiniz.
:: Not: "http://localhost:8000" adresinin calisiyor olmasi gerekir.

curl -s http://localhost:8000/api/cron.php > NUL
