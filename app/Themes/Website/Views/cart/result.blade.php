@extends('Website::layout')
@section('title','Đặt hàng thành công')
@section('description','Đặt hàng thành công')
@section('content')
<section class="pt-3 pb-3" style="background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%); min-height: 100vh;">
    <div class="container-lg">
        <div class="row justify-content-center">
            <div class="col-12 col-lg-11">
                <!-- Success Header -->
                <div class="text-center mb-2">
                    <div class="success-icon-wrapper mb-1">
                        <div class="success-icon">
                            <svg width="50" height="50" viewBox="0 0 80 80" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <circle cx="40" cy="40" r="40" fill="#10b981" opacity="0.1"/>
                                <circle cx="40" cy="40" r="32" fill="#10b981" opacity="0.2"/>
                                <path d="M40 8C22.326 8 8 22.326 8 40C8 57.674 22.326 72 40 72C57.674 72 72 57.674 72 40C72 22.326 57.674 8 40 8ZM32 52L24 44L27.17 40.83L32 45.66L52.83 24.83L56 28L32 52Z" fill="#10b981"/>
                            </svg>
                        </div>
                    </div>
                    <h1 class="success-title mb-1">Đặt hàng thành công!</h1>
                    <p class="success-message">Cảm ơn bạn đã đặt hàng. Đơn hàng của bạn đã được ghi nhận và đang được xử lý.</p>
                </div>

                <!-- Single Order Info Card -->
                <div class="order-info-card">
                    <div class="card-header">
                        <h3 class="card-title mb-0">
                            <svg width="14" height="14" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg" style="margin-right: 6px; vertical-align: middle;">
                                <path d="M10 2C5.58 2 2 5.58 2 10C2 14.42 5.58 18 10 18C14.42 18 18 14.42 18 10C18 5.58 14.42 2 10 2ZM10 16C6.69 16 4 13.31 4 10C4 6.69 6.69 4 10 4C13.31 4 16 6.69 16 10C16 13.31 13.31 16 10 16Z" fill="currentColor"/>
                                <path d="M9 6H11V11H9V6ZM9 12H11V14H9V12Z" fill="currentColor"/>
                            </svg>
                            Thông tin đơn hàng
                        </h3>
                    </div>
                    <div class="card-body">
                        <!-- 1. Thông tin người mua -->
                        <div class="info-section">
                            <h4 class="section-title">
                                <svg width="12" height="12" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg" style="margin-right: 6px; vertical-align: middle;">
                                    <path d="M10 10C12.7614 10 15 7.76142 15 5C15 2.23858 12.7614 0 10 0C7.23858 0 5 2.23858 5 5C5 7.76142 7.23858 10 10 10Z" fill="currentColor"/>
                                    <path d="M10 12C5.58172 12 2 13.7909 2 16V20H18V16C18 13.7909 14.4183 12 10 12Z" fill="currentColor"/>
                                </svg>
                                Thông tin người mua
                            </h4>
                            <div class="info-item">
                                <span class="info-label">Họ tên:</span>
                                <span class="info-value">{{$order->name}}</span>
                            </div>
                            <div class="info-item">
                                <span class="info-label">Điện thoại:</span>
                                <span class="info-value">{{$order->phone}}</span>
                            </div>
                            @if($order->email != "")
                            <div class="info-item">
                                <span class="info-label">Email:</span>
                                <span class="info-value">{{$order->email}}</span>
                            </div>
                            @endif
                            <div class="info-item">
                                <span class="info-label">Địa chỉ:</span>
                                <span class="info-value">{{$order->address}}, @if($order->ward) {{$order->ward->name}}, @endif @if($order->district) {{$order->district->name}}, @endif @if($order->province) {{$order->province->name}} @endif</span>
                            </div>
                        </div>

                        <div class="divider"></div>

                        <!-- 2. Sản phẩm mua -->
                        <div class="info-section">
                            <h4 class="section-title">
                                <svg width="12" height="12" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg" style="margin-right: 6px; vertical-align: middle;">
                                    <path d="M7 2C5.9 2 5 2.9 5 4V16C5 17.1 5.9 18 7 18H17C18.1 18 19 17.1 19 16V4C19 2.9 18.1 2 17 2H7ZM7 4H17V16H7V4Z" fill="currentColor"/>
                                    <path d="M3 6H1V18C1 19.1 1.9 20 3 20H15V18H3V6Z" fill="currentColor"/>
                                </svg>
                                Sản phẩm mua
                            </h4>
                            <div class="products-list">
                                @if($products->count() > 0)
                                @foreach($products as $product)
                                @php $slug = App\Modules\Product\Models\Product::select('slug')->where('id',$product->product_id)->first(); @endphp
                                <div class="product-item">
                                    <div class="product-info">
                                        <a href="{{getSlug($slug->slug)}}" target="_blank" class="product-name">{{$product->name}}</a>
                                        <span class="product-quantity">× {{$product->qty}}</span>
                                    </div>
                                    <div class="product-price">
                                        <span class="price-amount">{{number_format($product->price * $product->qty)}}₫</span>
                                    </div>
                                </div>
                                @endforeach
                                @endif
                            </div>
                        </div>

                        <div class="divider"></div>

                        <!-- 3. Thông tin đơn hàng -->
                        <div class="info-section">
                            <h4 class="section-title">
                                <svg width="12" height="12" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg" style="margin-right: 6px; vertical-align: middle;">
                                    <path d="M10 2C5.58 2 2 5.58 2 10C2 14.42 5.58 18 10 18C14.42 18 18 14.42 18 10C18 5.58 14.42 2 10 2ZM10 16C6.69 16 4 13.31 4 10C4 6.69 6.69 4 10 4C13.31 4 16 6.69 16 10C16 13.31 13.31 16 10 16Z" fill="currentColor"/>
                                    <path d="M9 6H11V11H9V6ZM9 12H11V14H9V12Z" fill="currentColor"/>
                                </svg>
                                Thông tin đơn hàng
                            </h4>
                            <div class="info-item">
                                <span class="info-label">Mã đơn hàng:</span>
                                <span class="info-value order-code">{{$order->code}}</span>
                            </div>
                            <div class="info-item">
                                <span class="info-label">Ngày đặt:</span>
                                <span class="info-value">{{formatDate($order->created_at)}}</span>
                            </div>
                            <div class="info-item">
                                <span class="info-label">Tổng giá trị:</span>
                                <span class="info-value">{{number_format($order->total)}}₫</span>
                            </div>
                            <div class="info-item">
                                <span class="info-label">Phí vận chuyển:</span>
                                <span class="info-value">{{number_format($order->fee_ship)}}₫</span>
                            </div>
                            @if($order->sale != 0)
                            <div class="info-item">
                                <span class="info-label">Giảm giá:</span>
                                <span class="info-value text-success">-{{number_format($order->sale)}}₫</span>
                            </div>
                            @endif
                            <div class="info-item total-row">
                                <span class="info-label">Tổng cộng:</span>
                                <span class="info-value total-amount">{{number_format($order->total + $order->fee_ship - $order->sale)}}₫</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="action-buttons text-center mt-2">
                    <a href="/" class="btn btn-primary me-2 mb-1">
                        <svg width="16" height="16" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg" style="margin-right: 6px; vertical-align: middle;">
                            <path d="M10 0L0 10H3V20H9V14H11V20H17V10H20L10 0Z" fill="currentColor"/>
                        </svg>
                        Tiếp tục mua sắm
                    </a>
                    <a href="/account/orders" class="btn btn-outline-primary mb-1">
                        <svg width="16" height="16" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg" style="margin-right: 6px; vertical-align: middle;">
                            <path d="M2 2H4V18H18V20H2V2Z" fill="currentColor"/>
                            <path d="M6 6H16V8H6V6Z" fill="currentColor"/>
                            <path d="M6 10H14V12H6V10Z" fill="currentColor"/>
                            <path d="M6 14H12V16H6V14Z" fill="currentColor"/>
                        </svg>
                        Xem đơn hàng
                    </a>
                </div>
            </div>
        </div>
    </div>
</section>

<style>
    /* Success Icon Animation */
    .success-icon-wrapper {
        animation: fadeInDown 0.6s ease-out;
    }
    
    .success-icon {
        display: inline-block;
        animation: scaleIn 0.5s ease-out 0.2s both;
    }
    
    @keyframes fadeInDown {
        from {
            opacity: 0;
            transform: translateY(-20px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }
    
    @keyframes scaleIn {
        from {
            transform: scale(0);
        }
        to {
            transform: scale(1);
        }
    }
    
    /* Success Title */
    .success-title {
        font-size: 1.5rem;
        font-weight: 700;
        color: #1f2937;
        animation: fadeInUp 0.6s ease-out 0.3s both;
    }
    
    .success-message {
        font-size: 12px;
        color: #6b7280;
        animation: fadeInUp 0.6s ease-out 0.4s both;
    }
    
    @keyframes fadeInUp {
        from {
            opacity: 0;
            transform: translateY(20px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }
    
    /* Card Styles */
    .order-info-card {
        background: #ffffff;
        border-radius: 16px;
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
        overflow: hidden;
        animation: fadeInUp 0.6s ease-out;
        transition: transform 0.3s ease, box-shadow 0.3s ease;
    }
    
    .order-info-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
    }
    
    .info-section {
        margin-bottom: 12px;
    }
    
    .info-section:last-child {
        margin-bottom: 0;
    }
    
    .section-title {
        font-size: 12px;
        font-weight: 600;
        color: #667eea;
        margin-bottom: 8px;
        display: flex;
        align-items: center;
    }
    
    .products-list {
        padding: 0;
    }
    
    .card-header {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        padding: 10px 12px;
        color: #ffffff;
    }
    
    .card-title {
        font-size: 12px;
        font-weight: 600;
        color: #ffffff;
        display: flex;
        align-items: center;
    }
    
    .card-body {
        padding: 12px;
    }
    
    /* Info Items */
    .info-item {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 4px 0;
    }
    
    .info-label {
        font-size: 12px;
        color: #6b7280;
        font-weight: 500;
    }
    
    .info-value {
        font-size: 12px;
        color: #1f2937;
        font-weight: 600;
    }
    
    .order-code {
        color: #667eea;
        font-size: 12px;
        font-weight: 700;
        letter-spacing: 0.5px;
    }
    
    .total-row {
        padding: 6px 0;
        border-top: 2px solid #e5e7eb;
        margin-top: 4px;
    }
    
    .total-row .info-label {
        font-size: 12px;
        color: #1f2937;
        font-weight: 700;
    }
    
    .total-amount {
        font-size: 14px;
        color: #10b981;
        font-weight: 700;
    }
    
    .divider {
        height: 1px;
        background: #e5e7eb;
        margin: 6px 0;
    }
    
    /* Products Table */
    .products-table {
        padding: 0;
    }
    
    .product-item {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 4px 0;
        border-bottom: 1px solid #f3f4f6;
        transition: background-color 0.2s ease;
    }
    
    .product-item:last-child {
        border-bottom: none;
    }
    
    .product-item:hover {
        background-color: #f9fafb;
    }
    
    .product-info {
        flex: 1;
    }
    
    .product-name {
        font-size: 12px;
        color: #1f2937;
        font-weight: 500;
        text-decoration: none;
        display: block;
        margin-bottom: 2px;
        transition: color 0.2s ease;
        line-height: 1.4;
    }
    
    .product-name:hover {
        color: #667eea;
    }
    
    .product-quantity {
        font-size: 12px;
        color: #6b7280;
    }
    
    .product-price {
        font-size: 12px;
        color: #1f2937;
        font-weight: 600;
    }
    
    /* Action Buttons */
    .action-buttons {
        margin-top: 12px;
        animation: fadeInUp 0.6s ease-out 0.5s both;
    }
    
    .btn-primary {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        border: none;
        padding: 8px 20px;
        font-weight: 600;
        font-size: 12px;
        border-radius: 6px;
        transition: all 0.3s ease;
        box-shadow: 0 2px 4px -1px rgba(102, 126, 234, 0.3);
    }
    
    .btn-primary:hover {
        transform: translateY(-1px);
        box-shadow: 0 4px 8px -2px rgba(102, 126, 234, 0.4);
    }
    
    .btn-outline-primary {
        border: 2px solid #667eea;
        color: #667eea;
        padding: 8px 20px;
        font-weight: 600;
        font-size: 12px;
        border-radius: 6px;
        transition: all 0.3s ease;
        background: transparent;
    }
    
    .btn-outline-primary:hover {
        background: #667eea;
        color: #ffffff;
        transform: translateY(-1px);
    }
    
    /* Responsive */
    @media (max-width: 768px) {
        .success-title {
            font-size: 2rem;
        }
        
        .success-message {
            font-size: 1rem;
        }
        
        .card-header {
            padding: 16px 20px;
        }
        
        .card-body {
            padding: 20px;
        }
        
        .info-item {
            flex-direction: column;
            align-items: flex-start;
        }
        
        .info-value {
            margin-top: 4px;
        }
        
        .product-item {
            flex-direction: column;
            align-items: flex-start;
        }
        
        .product-price {
            margin-top: 8px;
        }
        
        .action-buttons .btn {
            width: 100%;
            margin: 8px 0;
        }
    }
</style>
@endsection
