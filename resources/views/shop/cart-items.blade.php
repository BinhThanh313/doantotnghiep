@if(!empty($cart))
    @foreach($cart as $id => $item)
    <tr>
        <td class="align-middle">
            <input type="checkbox" class="form-check-input item-select" data-id="{{ $id }}"
                   data-subtotal="{{ $item['price'] * $item['quantity'] }}"
                   style="width: 1.2em; height: 1.2em;" checked
                   aria-label="Chọn sản phẩm để thanh toán">
        </td>
        <th scope="row">
            <div class="d-flex align-items-center">
                <a href="{{ route('shop.show', $item['id']) }}">
                    <img src="{{ img_url($item['image'] ?? null, asset('img/product-3.png')) }}" 
                         class="img-fluid me-5 rounded-circle" 
                         style="width: 80px; height: 80px;" alt="{{ $item['name'] }}">
                </a>
            </div>
        </th>
        <td>
            <a href="{{ route('shop.show', $item['id']) }}" class="text-dark text-decoration-none fw-bold">
                <p class="mb-0 mt-4">{{ $item['name'] }}</p>
            </a>
            @if(!empty($item['variant_name']))
                <small class="text-muted">{{ $item['variant_name'] }}</small>
            @endif
        </td>
        <td>
            <p class="mb-0 mt-4">{{ number_format($item['price'], 0, ',', '.') }}đ</p>
        </td>
        <td>
            <div class="input-group quantity mt-4" style="width: 130px;">
                <button type="button" class="btn btn-sm btn-minus rounded-circle bg-light border quantity-btn"
                        data-id="{{ $id }}" data-action="minus">
                    <i class="fa fa-minus"></i>
                </button>
                
                <input type="number" class="form-control form-control-sm text-center border-0 mx-1" 
                       value="{{ $item['quantity'] }}" min="1" readonly>
                
                <button type="button" class="btn btn-sm btn-plus rounded-circle bg-light border quantity-btn"
                        data-id="{{ $id }}" data-action="plus">
                    <i class="fa fa-plus"></i>
                </button>
            </div>
        </td>
        <td>
            <p class="mb-0 mt-4 fw-bold">{{ number_format($item['price'] * $item['quantity'], 0, ',', '.') }}đ</p>
        </td>
        <td>
            <button class="btn btn-md rounded-circle bg-light border mt-4 remove-item text-danger"
                    data-id="{{ $id }}">
                <i class="fa fa-times"></i>
            </button>
        </td>
    </tr>
    @endforeach
@else
    <tr>
        <td colspan="7" class="text-center py-5 text-danger fw-bold">
            Giỏ hàng của bạn đang trống! <br><br>
            <a href="{{ route('shop.index') }}" class="btn btn-primary mt-2">Tiếp tục mua sắm</a>
        </td>
    </tr>
@endif