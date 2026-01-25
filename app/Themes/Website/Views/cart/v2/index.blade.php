@extends('Website::layout')
@section('title','Giỏ hàng')
@section('description','Giỏ hàng của bạn')
@section('content')
<section class="mt-3 mb-5">
    <div class="container-lg">
        <div class="breadcrumb">
            <ol>
                <li><a href="/">Trang chủ</a></li>
                <li><a href="{{route('cart.index')}}">Giỏ hàng</a></li>
            </ol>
        </div>
        <h1 class="fs-24 fw-bold">Giỏ hàng</h1>
        <div class="row mt-3">
            <div class="col-12 col-md-8" id="cart-items-container">
                <div class="text-center py-5">
                    <div class="spinner-border" role="status">
                        <span class="visually-hidden">Đang tải...</span>
                    </div>
                </div>
            </div>
            <div class="col-12 col-md-4" id="cart-summary-container">
                <div class="text-center py-5">
                    <div class="spinner-border" role="status">
                        <span class="visually-hidden">Đang tải...</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<script>
document.addEventListener('DOMContentLoaded', function() {
    loadCart();
});

function loadCart() {
    fetch('{{route("cart.v2.json")}}')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                renderCart(data.data);
            } else {
                showError('Không thể tải giỏ hàng');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showError('Có lỗi xảy ra');
        });
}

function renderCart(cartData) {
    const itemsContainer = document.getElementById('cart-items-container');
    const summaryContainer = document.getElementById('cart-summary-container');
    
    if (!cartData.items || cartData.items.length === 0) {
        itemsContainer.innerHTML = '<div class="text-center py-5"><p>Giỏ hàng trống</p><a href="/" class="btn btn-primary">Tiếp tục mua sắm</a></div>';
        summaryContainer.innerHTML = '';
        return;
    }
    
    let itemsHtml = '<table class="table"><thead><tr><th>Sản phẩm</th><th>Giá</th><th>Số lượng</th><th>Tổng</th><th></th></tr></thead><tbody>';
    
    cartData.items.forEach(item => {
        itemsHtml += `
            <tr data-variant-id="${item.variant_id}">
                <td>
                    <div class="d-flex align-items-center">
                        <img src="${item.image || ''}" alt="${item.product_name}" style="width: 60px; height: 60px; object-fit: cover; margin-right: 10px;">
                        <div>
                            <div>${item.product_name}</div>
                            ${item.variant_name ? '<div class="text-muted small">' + item.variant_name + '</div>' : ''}
                        </div>
                    </div>
                </td>
                <td>${formatPrice(item.unit_price)}</td>
                <td>
                    <input type="number" class="form-control qty-input" value="${item.quantity}" min="1" style="width: 80px;" data-variant-id="${item.variant_id}">
                </td>
                <td class="item-subtotal">${formatPrice(item.subtotal)}</td>
                <td>
                    <button class="btn btn-sm btn-danger remove-item" data-variant-id="${item.variant_id}">Xóa</button>
                </td>
            </tr>
        `;
    });
    
    itemsHtml += '</tbody></table>';
    itemsContainer.innerHTML = itemsHtml;
    
    const summary = cartData.summary || {};
    summaryContainer.innerHTML = `
        <div class="card">
            <div class="card-header"><h5>Tổng đơn hàng</h5></div>
            <div class="card-body">
                <div class="d-flex justify-content-between mb-2">
                    <span>Tạm tính:</span>
                    <span>${formatPrice(summary.subtotal || 0)}</span>
                </div>
                <div class="d-flex justify-content-between mb-2">
                    <span>Giảm giá:</span>
                    <span class="text-success">-${formatPrice(summary.discount || 0)}</span>
                </div>
                <div class="d-flex justify-content-between mb-3">
                    <span><strong>Tổng cộng:</strong></span>
                    <span><strong>${formatPrice(summary.total || 0)}</strong></span>
                </div>
                <a href="{{route('checkout.v2.index')}}" class="btn btn-primary w-100">Tiến hành thanh toán</a>
            </div>
        </div>
    `;
    
    attachEventListeners();
}

function attachEventListeners() {
    document.querySelectorAll('.qty-input').forEach(input => {
        input.addEventListener('change', function() {
            const variantId = this.dataset.variantId;
            const qty = parseInt(this.value);
            if (qty > 0) {
                updateItem(variantId, qty);
            }
        });
    });
    
    document.querySelectorAll('.remove-item').forEach(btn => {
        btn.addEventListener('click', function() {
            const variantId = this.dataset.variantId;
            removeItem(variantId);
        });
    });
}

function updateItem(variantId, qty) {
    const url = '/cart/items/' + variantId;
    fetch(url, {
        method: 'PUT',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{csrf_token()}}'
        },
        body: JSON.stringify({ qty: qty })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            loadCart();
        } else {
            alert(data.message || 'Cập nhật thất bại');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Có lỗi xảy ra');
    });
}

function removeItem(variantId) {
    if (!confirm('Bạn có chắc muốn xóa sản phẩm này?')) return;
    
    const url = '/cart/items/' + variantId;
    fetch(url, {
        method: 'DELETE',
        headers: {
            'X-CSRF-TOKEN': '{{csrf_token()}}'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            loadCart();
        } else {
            alert(data.message || 'Xóa thất bại');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Có lỗi xảy ra');
    });
}

function formatPrice(price) {
    return new Intl.NumberFormat('vi-VN').format(price) + 'đ';
}

function showError(message) {
    document.getElementById('cart-items-container').innerHTML = `<div class="alert alert-danger">${message}</div>`;
}
</script>
@endsection

