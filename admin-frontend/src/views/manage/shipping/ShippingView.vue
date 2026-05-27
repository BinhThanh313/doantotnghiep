<script setup>
import { ref, onMounted } from 'vue'
import { mdiTruck, mdiPlus, mdiPencil, mdiTrashCan, mdiMapMarker } from '@mdi/js'
import SectionMain from '@/components/SectionMain.vue'
import CardBox from '@/components/CardBox.vue'
import CardBoxModal from '@/components/CardBoxModal.vue'
import FormField from '@/components/FormField.vue'
import FormControl from '@/components/FormControl.vue'
import LayoutAuthenticated from '@/layouts/LayoutAuthenticated.vue'
import SectionTitleLineWithButton from '@/components/SectionTitleLineWithButton.vue'
import BaseButton from '@/components/BaseButton.vue'
import BaseButtons from '@/components/BaseButtons.vue'
import api from '@/services/api'

// ==================== STATE ====================
const carriers = ref([])
const selectedCarrier = ref(null)
const zones = ref([])
const shipments = ref([])
const activeTab = ref('carriers') // 'carriers' | 'zones' | 'shipments'

// Modals
const isCarrierModalActive = ref(false)
const isZoneModalActive = ref(false)
const isEditCarrier = ref(false)
const isEditZone = ref(false)
const editingCarrierId = ref(null)
const editingZoneId = ref(null)

const carrierForm = ref({ name: '', code: '', base_fee: 0, per_km_fee: 0, is_active: true })
const zoneForm = ref({ province: '', region: '', fee: 0, estimated_days: 3 })

const regionOptions = [
  { id: '', label: 'Chọn miền' },
  { id: 'north', label: 'Miền Bắc' },
  { id: 'central', label: 'Miền Trung' },
  { id: 'south', label: 'Miền Nam' },
]

const shipmentStatusColors = {
  pending: 'bg-yellow-100 text-yellow-800',
  in_transit: 'bg-blue-100 text-blue-800',
  delivered: 'bg-emerald-100 text-emerald-800',
  failed: 'bg-red-100 text-red-800',
  returned: 'bg-gray-100 text-gray-800',
}

const shipmentStatusLabels = {
  pending: 'Chờ lấy hàng',
  in_transit: 'Đang vận chuyển',
  delivered: 'Đã giao',
  failed: 'Giao thất bại',
  returned: 'Hoàn hàng',
}

// ==================== CARRIERS ====================
const fetchCarriers = async () => {
  const res = await api.get('/api/admin/shipping/carriers')
  carriers.value = res.data
}

const saveCarrier = async () => {
  try {
    const payload = { ...carrierForm.value }
    if (payload.is_active && typeof payload.is_active !== 'boolean') {
      payload.is_active = Boolean(payload.is_active)
    }
    if (isEditCarrier.value) {
      await api.put(`/api/admin/shipping/carriers/${editingCarrierId.value}`, payload)
    } else {
      await api.post('/api/admin/shipping/carriers', payload)
    }
    isCarrierModalActive.value = false
    fetchCarriers()
  } catch (e) {
    alert(e.response?.data?.message || 'Lỗi lưu nhà vận chuyển')
  }
}

const openEditCarrier = (c) => {
  carrierForm.value = { name: c.name, code: c.code, base_fee: c.base_fee, per_km_fee: c.per_km_fee, is_active: c.is_active }
  editingCarrierId.value = c.id
  isEditCarrier.value = true
  isCarrierModalActive.value = true
}

const openCreateCarrier = () => {
  carrierForm.value = { name: '', code: '', base_fee: 0, per_km_fee: 0, is_active: true }
  isEditCarrier.value = false
  isCarrierModalActive.value = true
}

// ==================== ZONES ====================
const selectCarrier = async (carrier) => {
  selectedCarrier.value = carrier
  activeTab.value = 'zones'
  const res = await api.get(`/api/admin/shipping/carriers/${carrier.id}/zones`)
  zones.value = res.data
}

const saveZone = async () => {
  try {
    const payload = { ...zoneForm.value }
    if (payload.region?.id) payload.region = payload.region.id
    if (isEditZone.value) {
      await api.put(`/api/admin/shipping/zones/${editingZoneId.value}`, payload)
    } else {
      await api.post(`/api/admin/shipping/carriers/${selectedCarrier.value.id}/zones`, payload)
    }
    isZoneModalActive.value = false
    selectCarrier(selectedCarrier.value)
  } catch (e) {
    alert(e.response?.data?.message || 'Lỗi lưu khu vực')
  }
}

const openEditZone = (z) => {
  zoneForm.value = { province: z.province, region: z.region || '', fee: z.fee, estimated_days: z.estimated_days || 3 }
  editingZoneId.value = z.id
  isEditZone.value = true
  isZoneModalActive.value = true
}

const openCreateZone = () => {
  zoneForm.value = { province: '', region: '', fee: 0, estimated_days: 3 }
  isEditZone.value = false
  isZoneModalActive.value = true
}

const deleteZone = async (id) => {
  if (!confirm('Xóa khu vực này?')) return
  await api.delete(`/api/admin/shipping/zones/${id}`)
  selectCarrier(selectedCarrier.value)
}

// ==================== SHIPMENTS ====================
const fetchShipments = async () => {
  const res = await api.get('/api/admin/shipping/shipments')
  shipments.value = res.data.data || res.data
}

const formatPrice = (v) => new Intl.NumberFormat('vi-VN').format(v) + 'đ'
const formatDate = (d) => d ? new Date(d).toLocaleDateString('vi-VN') : '—'

onMounted(() => {
  fetchCarriers()
  fetchShipments()
})
</script>

<template>
  <LayoutAuthenticated>
    <SectionMain>
      <SectionTitleLineWithButton :icon="mdiTruck" title="Quản lý Vận chuyển" main />

      <!-- Tabs -->
      <div class="flex gap-2 mb-6">
        <button
          v-for="tab in [['carriers','Nhà vận chuyển'],['zones','Khu vực & Phí ship'],['shipments','Vận đơn']]"
          :key="tab[0]"
          @click="activeTab = tab[0]"
          class="px-4 py-2 rounded-lg text-sm font-medium transition-colors"
          :class="activeTab === tab[0] ? 'bg-blue-600 text-white' : 'bg-gray-100 dark:bg-slate-700 text-gray-700 dark:text-gray-300 hover:bg-gray-200'"
        >{{ tab[1] }}</button>
      </div>

      <!-- ===== TAB: CARRIERS ===== -->
      <template v-if="activeTab === 'carriers'">
        <CardBox class="mb-4">
          <div class="p-4 flex justify-between items-center">
            <h3 class="text-lg font-bold">Nhà vận chuyển</h3>
            <BaseButton :icon="mdiPlus" label="Thêm mới" color="success" small @click="openCreateCarrier" />
          </div>
        </CardBox>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
          <div v-for="c in carriers" :key="c.id"
            class="border rounded-xl p-4 bg-white dark:bg-slate-900/70 hover:shadow-md transition-shadow"
          >
            <div class="flex justify-between items-start mb-3">
              <div>
                <h4 class="font-bold text-lg">{{ c.name }}</h4>
                <span class="text-xs font-mono bg-gray-100 dark:bg-slate-700 px-2 py-0.5 rounded">{{ c.code }}</span>
              </div>
              <span class="px-2 py-1 rounded-full text-xs"
                :class="c.is_active ? 'bg-emerald-100 text-emerald-700' : 'bg-red-100 text-red-700'">
                {{ c.is_active ? 'Hoạt động' : 'Tắt' }}
              </span>
            </div>
            <div class="text-sm text-gray-600 dark:text-gray-400 space-y-1 mb-4">
              <p>Phí cơ bản: <strong class="text-gray-900 dark:text-white">{{ formatPrice(c.base_fee) }}</strong></p>
              <p>Phí/km: <strong class="text-gray-900 dark:text-white">{{ formatPrice(c.per_km_fee) }}</strong></p>
              <p>Số khu vực: <strong class="text-blue-600">{{ c.zones?.length || 0 }}</strong></p>
            </div>
            <div class="flex gap-2">
              <BaseButton :icon="mdiMapMarker" label="Khu vực" color="info" small @click="selectCarrier(c)" />
              <BaseButton :icon="mdiPencil" color="whiteDark" small @click="openEditCarrier(c)" />
            </div>
          </div>
          <div v-if="carriers.length === 0" class="col-span-full text-center py-8 text-gray-500">
            Chưa có nhà vận chuyển nào.
          </div>
        </div>
      </template>

      <!-- ===== TAB: ZONES ===== -->
      <template v-else-if="activeTab === 'zones'">
        <CardBox class="mb-4">
          <div class="p-4 flex justify-between items-center">
            <div>
              <h3 class="text-lg font-bold">
                Khu vực phí ship
                <span v-if="selectedCarrier" class="text-blue-600 ml-2">— {{ selectedCarrier.name }}</span>
              </h3>
              <p v-if="!selectedCarrier" class="text-sm text-gray-500 mb-0">
                Chọn nhà vận chuyển ở tab "Nhà vận chuyển" → nhấn "Khu vực"
              </p>
            </div>
            <BaseButton v-if="selectedCarrier" :icon="mdiPlus" label="Thêm khu vực" color="success" small @click="openCreateZone" />
          </div>
        </CardBox>

        <CardBox v-if="selectedCarrier" has-table>
          <table>
            <thead>
              <tr>
                <th>Tỉnh/Thành phố</th>
                <th>Miền</th>
                <th>Phí ship</th>
                <th>Dự kiến (ngày)</th>
                <th>Thao tác</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="z in zones" :key="z.id">
                <td data-label="Tỉnh/TP">{{ z.province }}</td>
                <td data-label="Miền">
                  <span class="px-2 py-0.5 rounded text-xs bg-gray-100 dark:bg-slate-700">
                    {{ { north: 'Bắc', central: 'Trung', south: 'Nam' }[z.region] || '—' }}
                  </span>
                </td>
                <td data-label="Phí"><span class="font-bold text-emerald-600">{{ formatPrice(z.fee) }}</span></td>
                <td data-label="Dự kiến">{{ z.estimated_days }} ngày</td>
                <td class="before:hidden lg:w-1 whitespace-nowrap">
                  <BaseButtons no-wrap>
                    <BaseButton color="info" :icon="mdiPencil" small @click="openEditZone(z)" />
                    <BaseButton color="danger" :icon="mdiTrashCan" small @click="deleteZone(z.id)" />
                  </BaseButtons>
                </td>
              </tr>
              <tr v-if="zones.length === 0">
                <td colspan="5" class="text-center py-8 text-gray-500">Chưa có khu vực nào. Nhấn "Thêm khu vực" để bắt đầu.</td>
              </tr>
            </tbody>
          </table>
        </CardBox>
      </template>

      <!-- ===== TAB: SHIPMENTS ===== -->
      <template v-else-if="activeTab === 'shipments'">
        <CardBox has-table>
          <table>
            <thead>
              <tr>
                <th>Mã đơn hàng</th>
                <th>Nhà VC</th>
                <th>Mã tracking</th>
                <th>Phí</th>
                <th>Trạng thái</th>
                <th>Dự kiến giao</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="s in shipments" :key="s.id">
                <td data-label="Đơn hàng">
                  <span class="font-mono text-sm font-bold text-blue-600">
                    {{ s.order?.tracking_number || '#' + s.order_id }}
                  </span>
                </td>
                <td data-label="Nhà VC">{{ s.carrier?.name || '—' }}</td>
                <td data-label="Tracking">
                  <span v-if="s.tracking_number" class="font-mono text-xs">{{ s.tracking_number }}</span>
                  <span v-else class="text-gray-400 text-xs">Chưa có</span>
                </td>
                <td data-label="Phí"><span class="text-emerald-600 font-bold">{{ formatPrice(s.shipping_fee || 0) }}</span></td>
                <td data-label="Trạng thái">
                  <span class="px-2 py-1 rounded-full text-xs font-medium" :class="shipmentStatusColors[s.status]">
                    {{ shipmentStatusLabels[s.status] || s.status }}
                  </span>
                </td>
                <td data-label="Dự kiến giao">{{ formatDate(s.estimated_delivery) }}</td>
              </tr>
              <tr v-if="shipments.length === 0">
                <td colspan="6" class="text-center py-8 text-gray-500">Chưa có vận đơn nào.</td>
              </tr>
            </tbody>
          </table>
        </CardBox>
      </template>

      <!-- Modal: Carrier -->
      <CardBoxModal
        v-model="isCarrierModalActive"
        :title="isEditCarrier ? 'Cập nhật Nhà vận chuyển' : 'Thêm Nhà vận chuyển'"
        button-label="Lưu"
        has-cancel
        is-form
        @confirm="saveCarrier"
      >
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <FormField label="Tên (*)"><FormControl v-model="carrierForm.name" required /></FormField>
          <FormField label="Mã code (*)"><FormControl v-model="carrierForm.code" placeholder="VD: ghn, viettelpost" required /></FormField>
          <FormField label="Phí cơ bản (đ)"><FormControl v-model="carrierForm.base_fee" type="number" /></FormField>
          <FormField label="Phí/km (đ)"><FormControl v-model="carrierForm.per_km_fee" type="number" /></FormField>
        </div>
        <div class="flex items-center gap-2 mt-4">
          <input type="checkbox" v-model="carrierForm.is_active" id="c_active" class="mr-2">
          <label for="c_active">Kích hoạt</label>
        </div>
      </CardBoxModal>

      <!-- Modal: Zone -->
      <CardBoxModal
        v-model="isZoneModalActive"
        :title="isEditZone ? 'Cập nhật Khu vực' : 'Thêm Khu vực'"
        button-label="Lưu"
        has-cancel
        is-form
        @confirm="saveZone"
      >
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <FormField label="Tỉnh/Thành phố (*)">
            <FormControl v-model="zoneForm.province" placeholder="VD: Hà Nội, TP.HCM" required />
          </FormField>
          <FormField label="Miền">
            <FormControl v-model="zoneForm.region" :options="regionOptions" />
          </FormField>
          <FormField label="Phí vận chuyển (đ) (*)">
            <FormControl v-model="zoneForm.fee" type="number" required />
          </FormField>
          <FormField label="Thời gian giao (ngày)">
            <FormControl v-model="zoneForm.estimated_days" type="number" />
          </FormField>
        </div>
      </CardBoxModal>

    </SectionMain>
  </LayoutAuthenticated>
</template>