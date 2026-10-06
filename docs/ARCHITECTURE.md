# Mimari kararlar

BayPdf, Laravel 12/13 ve PHP 8.3+ hedefleyen Composer paketidir. Jugend ile çalışma zamanı bağlantısı yoktur.

- Şablon sürümleri JSON belge tanımı saklar; renderer bu tanımı ve host verisini alır.
- Belge türleri ve değişkenler host servis sağlayıcısında kaydedilir.
- Yayınlanmış sürümler değişmez; değişiklik için yeni taslak oluşturulur.
- Yerleşim milimetre, metin boyutu punto cinsindedir.
- FPDF ailesindeki tFPDF ile Unicode çıktı ve endroid ile QR üretilir.
- İlk sürüm sabit sayfa yerleşimi kullanır; taşan içerik anlaşılır hatayla reddedilir.
- Görseller izin verilen özel storage önekiyle sınırlandırılır.
- Tasarımcı kendi derlenmiş Vue varlıklarıyla Composer arşivinde yer alır.
- Workbench ve örnek veriler yalnızca geliştirme içindir, dağıtım arşivine girmez.
- Yetkilendirme host authentication + manage-baypdf Gate ile sağlanır; ilk sürüm tek ortak şablon alanıdır.
