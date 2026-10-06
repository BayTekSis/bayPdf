# Sorun giderme

| Belirti | Çözüm |
|---|---|
| /baypdf 404 | enabled=true ve güncel route cache |
| 401 / giriş yönlendirmesi | Host auth, guard ve session |
| 403 | Gate ve mevcut kullanıcının izni |
| 419 | web middleware, CSRF ve session cookie |
| Tür listesi boş | Provider boot içinde DocumentTypes::register |
| 409 | Değişmiş/yayınlanmış sürümü yeniden yükleyin |
| 422 required | Bütün required değerleri ve örnekleri sağlayın |
| Metin sığmıyor | Kutuyu büyütün veya fontu küçültün |
| Görsel yok | PNG/JPEG, doğru disk/önek ve geçerli örnek anahtarı |
| Boş/eski ekran | baypdf-assets yeniden yayımlansın; browser console/Network incelensin |
| SQLite yok | composer workbench:prepare |
| Font yazılamıyor | font_cache dizinine PHP yazma yetkisi |
| Composer auth hatası | DEVELOPMENT.md içindeki izole ortam |
| Windows lean hatası | composer dist:check |

Başka makineden taşınan tFPDF cache dosyaları eski mutlak yollar taşıyabilir. Yalnızca BayPdf font cache dizinini yeniden oluşturun; asset veya belge alanlarını temizlemeyin.

Canvas yaklaşık görünüm; gerçek sonuç server PDF önizlemesidir. Gerçek veriye yakın örnekler kullanın; kısa örnekle sığan metin gerçek veride taşabilir.

Başka paket _SYSTEM_TTFONTS global sabitini kullanıyorsa tFPDF'nin font seçimini etkileyebilir. Custom font koleksiyonu ve karmaşık yazı şekillendirme bu sürümde yoktur.
