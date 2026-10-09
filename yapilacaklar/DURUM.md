# Durum

## 2026-10-09 Finance readiness çalışması

Repository truth audit tamamlandı. Mevcut kod shared template/asset alanı, scalar değişkenler ve fixed single-page absolute layout kullanıyor; schema snapshot, published immutability ve `lock_version` davranışları korunacak. `docs/codex/` altında continuity sistemi ve 65 objektif kabul kriterli master roadmap oluşturuldu. Başlangıç doğrulaması: 34 backend testi/80 assertion ve 2 frontend testi PASS; 4 mevcut Playwright senaryosu envantere alındı.

Generic scope logical unit tamamlandı: public `ScopeResolver` contract'ı, default shared mod, enabled fail-closed davranış, indexed nullable `scope_key`, scoped TemplateManager lifecycle/render çağrıları ve template/version route binding eklendi. Legacy null satırlar scoped modda gizli kalır ve yalnız host'un doğrulanmış ID migration'ıyla sahiplenilir. Targeted doğrulama 24 test/77 assertion PASS.

Sonraki mantıksal adım: Scoped asset storage prefix, list/read isolation ve template/image variable reference kontrolleri.


Yerel paket oluşturma kapsamı: 4/4 faz tamamlandı.

- Bağımsız Composer paketi, Testbench/workbench ve Package Boost altyapısı mevcut.
- Değişken kaydı, sürümlü şablonlar, Unicode PDF ve yetkili Vue tasarımcı tamamlandı.
- GitHub: https://github.com/BayTekSis/bayPdf, public, master branch'i.
- Lisans: MIT. Telif sahibi: BayPass. Geliştirici: Mehmet BAYINDIR.
- Composer adı mevcut `bay/baypdf` olarak korundu; Packagist vendor sahipliği henüz doğrulanmadı.

## 2026-10-07 yayın hazırlığı

README, kurulum/yayın belgeleri ve üçüncü taraf bildirimleri güncellendi. Composer homepage/support/keywords bilgileri eklendi. GitHub güvenlik politikası oluşturuldu. `.idea/` ve yerel release notları Git/dağıtım dışında bırakıldı; kullanıcı dosyaları silinmedi.

GitHub Actions'a dağıtım arşivini kuran `distribution` işi eklendi. Yerel kontroller: 31 backend testi / 77 assertion, 2 frontend testi, 2 Chromium senaryosu, build, Pint, PHPStan, dağıtım ve arşivden dev bağımlılıkları olmadan kurulum PASS. Detay ve doğrulanmayan alanlar docs/VERIFICATION.md içinde.

## 2026-10-07 kapsamlı kontrol

Tarih girdisi, öğe/değişken listeleri ve özel grup adlarıyla ilgili dört hata düzeltildi. Kalabalık şablon kütüphanesi kendi alanında kaydırılarak araçların ekran dışına itilmesi giderildi. Yeni regresyon testleri düzeltme öncesinde başarısız, sonrasında başarılı çalıştı.

Son kontrol: 34 backend testi / 80 assertion, 2 frontend testi, 4 Chromium senaryosu, production build, Pint, PHPStan ve dağıtım kontrolü PASS. Masaüstü/mobil görüntüler incelendi. Güncel ZIP arşivi geliştirme dosyalarından arındırılmış; ayrı dizinde runtime kurulum, platform, autoload ve asset kontrolleri PASS. Composer runtime ve npm audit güvenlik bildirimi bulmadı. Kanıt ve sınırlar docs/VERIFICATION.md içinde.

## Dış adımlar

- `0.1.0` etiketi ve `v0.1.0` GitHub release, 2026-10-08 tarihinde kullanıcının açık yayın talebiyle oluşturuldu. Etiket: `67903e737ffde7b90890c4cf15b635adcc280390`.
- Master CI `37744943786` ve etiket CI `37751728725`: 11 işin tamamı başarılı. Yerel QA, build, 2 frontend ve 4 Chromium testi yeniden geçti.
- Release sonrası `Update changelog` işi (`37751897045`) commit adımında 128 ile başarısız oldu. Changelog, yayımlanan release gövdesinden yerelde tamamlandı. Release hedefi SHA; workflow bu değeri checkout edip branch belirtmeden push ediyor. Hedefi `master` olarak güncelleme denemesi GitHub kimliği alınamadığı için tamamlanmadı; etiket değiştirilmedi.
- Private vulnerability reporting etkinleştirilmeli. Kontrolde kapalıydı; terminalde GitHub yazma kimliği bulunamadı.
- Packagist `bay/baypdf` API kontrolü 404 döndü. Paket sahibi hesabıyla repository kaydı ve webhook kurulumu bekleniyor; bağlı tarayıcı oturumu yok.
- Changelog telafisi `cf912fc` commit'iyle master'a gönderildi. Başarısız otomatik işin sonucu değiştirilmedi; sürüm etiketi aynı commit'te tutuldu.
- Sürüm notları `internal/release-notes-0.1.0.md` içinde doğrulanmış SHA'ya bağlıdır. Composer manifestine version eklenmedi; sürüm Git etiketinden okunur.

GitHub CLI bu terminalde yok; Git credential lookup başarısız oldu. Codex CLI bağımsız inceleme wrapper'ı da uygun CLI bulamadı; yayın diff'i ayrı agent tarafından salt okunur incelendi.

Sınırlar: tek sayfalı sabit yerleşim, ortak yönetim alanı; tenant izolasyonu, otomatik çok sayfa ve tablo akışı yok. Jugend'e yazılmadı.
