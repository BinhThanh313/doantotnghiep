@if(session('cart') && count(session('cart')) > 0)
    @foreach($cart as $id => $item)
    <tr>
        <th scope="row">
            <div class="d-flex align-items-center">
                <img src="{{ !empty($item['image']) ? asset('storage/' . $item['image']) : asset('img/product-3.png') }}" 
                     class="img-fluid me-5 rounded-circle" 
                     style="width: 80px; height: 80px;" alt="{{ $item['name'] }}">
            </div>
        </th>
        <td>
            <p class="mb-0 mt-4">{{ $item['name'] }}</p>
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
        <td colspan="6" class="text-center py-5 text-danger fw-bold">
            Giỏ hàng của bạn đang trống! <br><br>
            <a href="{{ route('shop.index') }}" class="btn btn-primary mt-2">Tiếp tục mua sắm</a>
        </td>
    </tr>
@endif