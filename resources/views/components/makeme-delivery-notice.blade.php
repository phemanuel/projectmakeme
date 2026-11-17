<style>

/* ==========================================
   PROJECT MAKEME - COMPACT DELIVERY NOTICE
========================================== */

.mk-delivery-notice {
    display: flex;
    align-items: center;
    gap: 14px;
    position: relative;

    margin: 14px 0 20px;
    padding: 14px 18px;

    border: 1px solid rgba(244, 161, 22, 0.30);
    border-left: 4px solid #f4a116;
    border-radius: 12px;

    background: #fffaf0;
    box-shadow: 0 4px 14px rgba(0, 0, 0, 0.04);

    color: #242424;
}

.mk-delivery-icon {
    display: flex;
    align-items: center;
    justify-content: center;

    width: 44px;
    height: 44px;
    flex: 0 0 44px;

    border-radius: 12px;
    background: #fff0d0;
    font-size: 23px;

    animation: mkDeliveryPulse 3s ease-in-out infinite;
}

.mk-delivery-content {
    flex: 1;
    min-width: 0;
}

.mk-delivery-content h3 {
    margin: 0 0 5px;
    font-size: 15px;
    line-height: 1.35;
    font-weight: 800;
    color: #252525;
}

.mk-delivery-content p {
    margin: 0;
    font-size: 13px;
    line-height: 1.65;
    color: #444;
}

.mk-delivery-content strong {
    font-weight: 800;
    color: #171717;
}

.mk-delivery-time {
    font-size: 11px;
    color: #8a6c39;
    white-space: nowrap;
}

/* Subtle animation on icon only */

@keyframes mkDeliveryPulse {
    0%, 100% {
        transform: scale(1);
    }

    50% {
        transform: scale(1.08);
    }
}

/* Mobile */

@media (max-width: 600px) {
    .mk-delivery-notice {
        align-items: flex-start;
        gap: 10px;
        padding: 12px;
        margin: 12px 0 16px;
    }

    .mk-delivery-icon {
        width: 36px;
        height: 36px;
        flex-basis: 36px;
        font-size: 19px;
    }

    .mk-delivery-content h3 {
        font-size: 13px;
    }

    .mk-delivery-content p {
        font-size: 12px;
        line-height: 1.55;
    }

    .mk-delivery-time {
        display: inline;
        font-size: 10px;
        white-space: normal;
    }
}

@media (prefers-reduced-motion: reduce) {
    .mk-delivery-icon {
        animation: none;
    }
}

</style>
<div class="mk-delivery-notice" role="note">
    <div class="mk-delivery-icon" aria-hidden="true">
        🚚
    </div>

    <div class="mk-delivery-content">
        <h3>
            Important: Marketplace Delivery Schedule
        </h3>

        <p>
            Orders received <strong>on or before 10:00 AM</strong>
            will be delivered <strong>the same evening</strong>.
            Orders received <strong>after 10:00 AM</strong>
            will be delivered <strong>the following evening</strong>.
            <span class="mk-delivery-time">All times are WAT.</span>
        </p>
    </div>
</div>