            <section class="cp-panel cp-supply-panel" aria-labelledby="cp-supply-title">
                <div class="cp-supply-heading"><div><p class="cp-overline">THE CREATIVE MARKETPLACE</p><h3 id="cp-supply-title">Featured Supplies</h3><p>Browse the essentials available to contestants. Purchase supplies securely using your Kolo Money credits.</p></div><div style="display:flex;align-items:center;gap:10px;flex-wrap:wrap"><span class="cp-supply-count">{{ number_format($availableProducts) }} available</span><a href="{{ route('makeme.participant.marketplace.index') }}" style="display:inline-flex;background:#171615;color:#e9c975;padding:8px 12px;border-radius:8px;font-weight:800;font-size:11px;white-space:nowrap">View all products →</a></div></div>
                <div class="cp-products">
                    @forelse($products as $product)
                        <article class="cp-product"><div class="cp-product-photo">
                            @if($product->image_path)
                                <img src="{{ asset('storage/' . ltrim($product->image_path, '/')) }}" alt="{{ $product->name }}" loading="lazy">
                            @else
                                <div class="cp-product-fallback"><span>✦</span><small>MAKEME LIVE</small></div>
                            @endif
                            <span class="cp-stock-pill">In stock</span>
                        </div><div class="cp-product-info"><span class="cp-product-category">{{ ucwords(str_replace(['_', '-'], ' ', (string) ($product->category ?? 'General'))) }}</span><h4>{{ $product->name }}</h4><div class="cp-product-price"><strong>{{ number_format((int) $product->credit_price) }}</strong><span>credits</span><a href="{{ route('makeme.participant.marketplace.show', $product) }}" style="display:inline-block;margin-top:12px;color:#926e31;font-size:11px;font-weight:800">View &amp; purchase →</a></div></article>
                    @empty
                        <div class="cp-products-empty"><span>✦</span><strong>The Marketplace is getting ready</strong><p>Available products will appear here once they are added.</p></div>
                    @endforelse
                </div>
            </section>
            <div class="cp-bottom-note"><span class="cp-note-icon">i</span><p><strong>Marketplace is open:</strong> Prices are in Kolo Money credits. Purchases are deducted from your wallet when you confirm an order. <a href="{{ route('makeme.participant.orders.index') }}" style="color:#967034;font-weight:800">Track my orders →</a></p></div>
