document.addEventListener('DOMContentLoaded', function() {
    if (typeof currentProductId !== 'undefined') {
        fetchReviews(currentProductId, 1);
    }
});

function fetchReviews(productId, page = 1) {
    fetch(`${appBaseUrl}/api/products/${productId}/reviews?page=${page}`)
        .then(res => res.json())
        .then(data => {
            renderReviews(data.reviews.data);
            renderPagination(data.reviews, productId);
            if (document.getElementById('review-avg-rating')) {
                document.getElementById('review-avg-rating').innerText = `⭐ ${data.avg_rating} / 5 (${data.total} đánh giá)`;
            }
            if (document.getElementById('review-tab-count')) {
                document.getElementById('review-tab-count').innerText = data.total;
            }
        })
        .catch(err => console.error('Lỗi tải đánh giá:', err));
}

function renderReviews(reviews) {
    const container = document.getElementById('reviews-container');
    if (!reviews || reviews.length === 0) {
        container.innerHTML = '<p class="text-muted">Chưa có đánh giá nào cho sản phẩm này.</p>';
        return;
    }

    let html = '';
    reviews.forEach(r => {
        let stars = '⭐'.repeat(r.rating);
        let mediaHtml = '';

        if ((r.images && r.images.length > 0) || r.video_url) {
            mediaHtml = '<div class="d-flex flex-wrap gap-2 mt-3">';
            if (r.images && r.images.length > 0) {
                r.images.forEach(img => {
                    let src = img.image_url.startsWith('http') ? img.image_url : `${appBaseUrl}/storage/${img.image_url}`;
                    mediaHtml += `<img src="${src}" class="rounded border" style="width: 80px; height: 80px; object-fit: cover;">`;
                });
            }
            if (r.video_url) {
                let vSrc = r.video_url.startsWith('http') ? r.video_url : `${appBaseUrl}/storage/${r.video_url}`;
                mediaHtml += `<video src="${vSrc}" controls class="rounded border" style="width: 160px; height: 90px; object-fit: cover;"></video>`;
            }
            mediaHtml += '</div>';
        }

        let verifiedTag = r.verified_purchase ? '<span class="badge bg-success ms-2"><i class="fa fa-check-circle"></i> Đã mua hàng</span>' : '';

        html += `
            <div class="review-item mb-4 pb-4 border-bottom">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <div>
                        <strong class="fs-6">${r.user?.name || 'Ẩn danh'}</strong>
                        ${verifiedTag}
                    </div>
                    <small class="text-muted">${new Date(r.created_at).toLocaleDateString('vi-VN')}</small>
                </div>
                <div class="text-warning mb-2">${stars}</div>
                ${r.title ? `<h6 class="fw-bold mb-1">${r.title}</h6>` : ''}
                <p class="mb-0 text-dark">${r.comment || ''}</p>
                ${mediaHtml}
            </div>
        `;
    });
    container.innerHTML = html;
}

function renderPagination(paginationData, productId) {
    const container = document.getElementById('reviews-pagination');
    if (paginationData.last_page <= 1) {
        container.innerHTML = '';
        return;
    }

    let html = `<ul class="pagination">`;
    for (let i = 1; i <= paginationData.last_page; i++) {
        let active = i === paginationData.current_page ? 'active' : '';
        html += `<li class="page-item ${active}">
                    <button class="page-link" onclick="fetchReviews(${productId}, ${i})">${i}</button>
                 </li>`;
    }
    html += `</ul>`;
    container.innerHTML = html;
}

async function submitReview(e) {
    e.preventDefault();
    const form = e.target;
    const formData = new FormData(form);
    const btn = document.getElementById('submit-review-btn');
    const msg = document.getElementById('review-message');
    
    btn.disabled = true;
    btn.innerHTML = '<i class="fa fa-spinner fa-spin"></i> Đang gửi...';
    msg.innerHTML = '';

    try {
        const response = await fetch(`${appBaseUrl}/api/products/${currentProductId}/reviews`, {
            method: 'POST',
            headers: {
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
            },
            body: formData
        });

        const data = await response.json();
        
        if (response.ok) {
            form.reset();
            fetchReviews(currentProductId, 1);
        } else {
            msg.innerHTML = `<div class="alert alert-danger">${data.message || 'Lỗi khi gửi đánh giá.'}</div>`;
        }
    } catch (error) {
        msg.innerHTML = '<div class="alert alert-danger">Lỗi kết nối máy chủ. Vui lòng thử lại sau.</div>';
    }
    
    btn.disabled = false;
    btn.innerText = 'Gửi đánh giá';
}