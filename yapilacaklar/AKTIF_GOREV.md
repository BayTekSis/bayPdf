# Aktif görev

Amaç: BayPdf'nin kodunu, tasarımcı görünümünü ve Composer dağıtımını kapsamlı incelemek; doğrulanan hataları mevcut master branch'inde düzeltmek.

## Kapsamlı kontrol

- [x] Runtime kaynakları, API yetkilendirmesi, sürüm yönetimi, dosyalar ve paket manifesti incelendi.
- [x] Null byte tarih hatası, öğe/değişken listesi doğrulaması ve özel grup adları için regresyon testleri önce başarısız çalıştırıldı, ardından düzeltildi.
- [x] Kalabalık kütüphanenin araçları ekran dışına itmesi testle doğrulandı ve kaydırılabilir listeyle düzeltildi.
- [x] 34 backend testi / 80 assertion, 2 frontend testi, 4 Chromium senaryosu, Pint, PHPStan ve build geçti.
- [x] Son dağıtım arşivi yeniden kontrol edildi ve dokümantasyon tamamlandı.

## Önceki yayın hazırlığı ve dış adımlar

- [x] Git durumu, talimatlar, paket metadatası ve belgeler incelendi.
- [x] MIT/BayPass lisans bilgileri ve üçüncü taraf atıfları tutarlı hâle getirildi.
- [x] README, kurulum ve yayın rehberleri mevcut GitHub repository'sine göre güncellendi.
- [x] Composer destek/kaynak bağlantıları ve güvenlik politikası eklendi.
- [x] Yerel IDE dosyaları ve release notları Git/dağıtım dışında bırakıldı.
- [x] QA, frontend, build, tarayıcı ve arşivden runtime kurulum kontrolü geçti.
- [x] Dağıtım doğrulaması CI'a eklendi; YAML parse ve bağımsız diff incelemesi yapıldı.
- [x] Final kod commit'i GitHub'a gönderildi ve o SHA'nın tüm test/dağıtım CI işleri geçti.
- [ ] GitHub özel güvenlik bildirimi etkinleştirildi.
- [ ] Packagist vendor erişimi ve kayıt/webhook tamamlandı.
- [x] Yeşil commit'e bağlı release notları hazırlandı; açık yayın talebi üzerine 0.1.0 etiketi ve GitHub sürümü yayımlandı.
- [x] Etiket üzerindeki 11 test/dağıtım CI işi geçti.
- [x] Başarısız otomatik changelog işleminin telafisi `cf912fc` commit'iyle GitHub'a aktarıldı.

Durum: GitHub v0.1.0 yayımlandı, etiket CI başarılı ve changelog telafisi GitHub'a aktarıldı. Packagist hesabına erişim/kayıt bekleniyor. Ayrıntılar DURUM.md içinde.
