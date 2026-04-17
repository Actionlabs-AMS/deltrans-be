# Shipping Line Reference (Live DB)

Generated from live tables using MCP:
- `shipping_lines`
- `rate_per_clients`
- `fixed_expenses`
- `soa_data_options` (for `transaction_information_template` names)

## SOA Transaction Information Option Map

- `11` Date
- `12` Booking Number
- `13` Origin
- `14` Destination
- `15` Waybill
- `16` Remarks
- `17` Plate Number
- `18` Container Number
- `19` Size
- `20` Vessel
- `21` Work Order
- `22` Stack Run
- `23` 12% VAT
- `24` Amount
- `25` Total Amount

---

## 1) ONE (`shipping_lines.id = 1`)

- **shippingline info**
  - name: `OCEAN NETWORK EXPRESS PTE LTD`
  - short_name: `ONE`
  - email_address: `shipping-line+ocean-network-express-pte-ltd+placeholder@deltrans.local`
  - address: `Unit No. 907A-910, 9th Floor West Tower 8912 Asean Ave. Bldg, corner Asean st. Asean City Paranaque City`
- **transaction_information_template**
  - IDs: `[12, 13, 14, 18, 19, 21, 24]`
  - names: `[Booking Number, Origin, Destination, Container Number, Size, Work Order, Amount]`
- **rate per client**
  - `CY: ALL CY, container_size: 20ft, rate: 6700, stack_run: 0, tax_percent: 12, no_of_days: 45`
  - `CY: ALL CY, container_size: 40ft, rate: 6400, stack_run: 0, tax_percent: 12, no_of_days: 45`
  - `CY: ALL CY, container_size: 20ft(offhire), rate: 8000, stack_run: 0, tax_percent: 12, no_of_days: 45`
  - `CY: ALL CY, container_size: 40ft(offhire), rate: 7500, stack_run: 0, tax_percent: 12, no_of_days: 45`
  - requirements: `BILLING STATEMENT/SOA/WOR KORDER`
- **fixed expense []**
  - `container_size: 20ft, cypa_from: NCT, cypa_to: MIP, docs_fee: 1200.00, online_booking_fee: 150.00, stack_run: 0.00, expenses: 175.00, total_expenses: 1525.00`
  - `container_size: 40ft, cypa_from: NCT, cypa_to: MIP, docs_fee: 1200.00, online_booking_fee: 150.00, stack_run: 0.00, expenses: 300.00, total_expenses: 1650.00`
  - `container_size: 40ft, cypa_from: NCT, cypa_to: ECD, docs_fee: 800.00, online_booking_fee: 0.00, stack_run: 0.00, expenses: 350.00, total_expenses: 1150.00`
  - `container_size: 20ft, cypa_from: NCT, cypa_to: PIER16PSACC, docs_fee: 1200.00, online_booking_fee: 0.00, stack_run: 0.00, expenses: 260.00, total_expenses: 1460.00`
  - `container_size: 20ft, cypa_from: NCT, cypa_to: PIER16LORENZO, docs_fee: 1200.00, online_booking_fee: 0.00, stack_run: 0.00, expenses: 260.00, total_expenses: 1460.00`
  - `container_size: 40ft, cypa_from: NCT, cypa_to: PIER16LORENZO, docs_fee: 1200.00, online_booking_fee: 0.00, stack_run: 0.00, expenses: 520.00, total_expenses: 1720.00`
  - `container_size: 40ft, cypa_from: NCT, cypa_to: SMY, docs_fee: 1050.00, online_booking_fee: 150.00, stack_run: 1344.00, expenses: 710.00, total_expenses: 3254.00`
  - `container_size: 20ft, cypa_from: SEACON, cypa_to: MIP, docs_fee: 600.00, online_booking_fee: 170.00, stack_run: 0.00, expenses: 175.00, total_expenses: 945.00`
  - `container_size: 40ft, cypa_from: SEACON, cypa_to: MIP, docs_fee: 600.00, online_booking_fee: 170.00, stack_run: 0.00, expenses: 300.00, total_expenses: 1070.00`
  - `container_size: 40ft, cypa_from: SEACON, cypa_to: ECD, docs_fee: 600.00, online_booking_fee: 0.00, stack_run: 0.00, expenses: 350.00, total_expenses: 950.00`
  - `container_size: 20ft, cypa_from: SEACON, cypa_to: PIER16PSACC, docs_fee: 600.00, online_booking_fee: 0.00, stack_run: 0.00, expenses: 325.00, total_expenses: 925.00`
  - `container_size: 20ft, cypa_from: SEACON, cypa_to: PIER16LORENZO, docs_fee: 600.00, online_booking_fee: 0.00, stack_run: 0.00, expenses: 335.00, total_expenses: 935.00`
  - `container_size: 40ft, cypa_from: SEACON, cypa_to: PIER16LORENZO, docs_fee: 600.00, online_booking_fee: 0.00, stack_run: 0.00, expenses: 220.00, total_expenses: 820.00`
  - `container_size: 40ft, cypa_from: SEACON, cypa_to: SMY, docs_fee: 500.00, online_booking_fee: 0.00, stack_run: 1344.00, expenses: 580.00, total_expenses: 2424.00`
  - `container_size: 40ft, cypa_from: MIP, cypa_to: NCT, docs_fee: 1200.00, online_booking_fee: 0.00, stack_run: 0.00, expenses: 470.00, total_expenses: 1670.00`
  - `container_size: 40ft, cypa_from: MIP, cypa_to: SEACON, docs_fee: 600.00, online_booking_fee: 0.00, stack_run: 0.00, expenses: 330.00, total_expenses: 930.00`

## 2) KMTC (`shipping_lines.id = 2`)

- **shippingline info**
  - name: `KOREA MARINE TRANSPORT CO LTD`
  - short_name: `KMTC`
- **transaction_information_template**
  - IDs: `[11, 13, 14, 15, 16, 17, 18, 19, 20, 24]`
  - names: `[Date, Origin, Destination, Waybill, Remarks, Plate Number, Container Number, Size, Vessel, Amount]`
- **rate per client**
  - `CY: ALL CY, container_size: 20ft, rate: 7150, stack_run: 0, tax_percent: 12, no_of_days: 15`
  - `CY: ALL CY, container_size: 40ft, rate: 7150, stack_run: 0, tax_percent: 12, no_of_days: 15`
- **fixed expense []**
  - `container_size: 20ft, cypa_from: MARINA, cypa_to: MIP, docs_fee: 800.00, online_booking_fee: 0.00, stack_run: 0.00, expenses: 175.00, total_expenses: 975.00`
  - `container_size: 40ft, cypa_from: MARINA, cypa_to: MIP, docs_fee: 800.00, online_booking_fee: 0.00, stack_run: 0.00, expenses: 300.00, total_expenses: 1100.00`
  - `container_size: 20ft, cypa_from: NCT, cypa_to: MIP, docs_fee: 1350.00, online_booking_fee: 0.00, stack_run: 0.00, expenses: 175.00, total_expenses: 1525.00`
  - `container_size: 40ft, cypa_from: NCT, cypa_to: MIP, docs_fee: 1350.00, online_booking_fee: 0.00, stack_run: 0.00, expenses: 300.00, total_expenses: 1650.00`
  - `container_size: 20ft, cypa_from: SEACON, cypa_to: MIP, docs_fee: 600.00, online_booking_fee: 0.00, stack_run: 0.00, expenses: 175.00, total_expenses: 775.00`
  - `container_size: 40ft, cypa_from: SEACON, cypa_to: MIP, docs_fee: 1200.00, online_booking_fee: 0.00, stack_run: 0.00, expenses: 300.00, total_expenses: 1500.00`
  - `container_size: 20ft, cypa_from: BRIGHTPOINT, cypa_to: MIP, docs_fee: 1100.00, online_booking_fee: 0.00, stack_run: 0.00, expenses: 175.00, total_expenses: 1275.00`
  - `container_size: 40ft, cypa_from: BRIGHTPOINT, cypa_to: MIP, docs_fee: 1100.00, online_booking_fee: 0.00, stack_run: 0.00, expenses: 300.00, total_expenses: 1400.00`
  - `container_size: 40ft, cypa_from: SOUTH, cypa_to: MIP, docs_fee: 450.00, online_booking_fee: 0.00, stack_run: 0.00, expenses: 0.00, total_expenses: 450.00`

## 3) OOCL (`shipping_lines.id = 3`)

- **shippingline info**
  - name: `ORIENT OVERSEAS CONTAINER LINE, LTD`
  - short_name: `OOCL`
- **transaction_information_template**
  - IDs: `[11, 12, 13, 14, 15, 16, 17, 18, 19, 22, 24]`
  - names: `[Date, Booking Number, Origin, Destination, Waybill, Remarks, Plate Number, Container Number, Size, Stack Run, Amount]`
- **rate per client**
  - `CY: TOCSI, container_size: 20ft, rate: 6000, stack_run: 112, tax_percent: 12, no_of_days: 30`
  - `CY: TOCSI, container_size: 40ft, rate: 6500, stack_run: 112, tax_percent: 12, no_of_days: 30`
  - `CY: SEACON, container_size: 20ft, rate: 8000, stack_run: 112, tax_percent: 12, no_of_days: 30`
  - `CY: SEACON, container_size: 40ft, rate: 7000, stack_run: 112, tax_percent: 12, no_of_days: 30`
- **fixed expense []**
  - `container_size: 20ft, cypa_from: TOCSI, cypa_to: SOUTH, docs_fee: 675.00, online_booking_fee: 0.00, stack_run: 0.00, expenses: 340.00, total_expenses: 1015.00`
  - `container_size: 40ft, cypa_from: TOCSI, cypa_to: SOUTH, docs_fee: 650.00, online_booking_fee: 0.00, stack_run: 0.00, expenses: 500.00, total_expenses: 1150.00`
  - `container_size: 20ft, cypa_from: TOCSI, cypa_to: MIP, docs_fee: 650.00, online_booking_fee: 0.00, stack_run: 0.00, expenses: 175.00, total_expenses: 825.00`
  - `container_size: 40ft, cypa_from: TOCSI, cypa_to: MIP, docs_fee: 650.00, online_booking_fee: 0.00, stack_run: 0.00, expenses: 270.00, total_expenses: 920.00`
  - `container_size: 20ft, cypa_from: TOCSI, cypa_to: PIER16TRANSASIA, docs_fee: 500.00, online_booking_fee: 0.00, stack_run: 0.00, expenses: 275.00, total_expenses: 775.00`
  - `container_size: 40ft, cypa_from: TOCSI, cypa_to: CAVITE, docs_fee: 650.00, online_booking_fee: 0.00, stack_run: 0.00, expenses: 0.00, total_expenses: 650.00`
  - `container_size: 20ft, cypa_from: TOCSI, cypa_to: SEACON, docs_fee: 1000.00, online_booking_fee: 0.00, stack_run: 0.00, expenses: 275.00, total_expenses: 1275.00`
  - `container_size: 40ft, cypa_from: SEACON, cypa_to: SOUTH, docs_fee: 1000.00, online_booking_fee: 0.00, stack_run: 0.00, expenses: 550.00, total_expenses: 1550.00`
  - `container_size: 20ft, cypa_from: SEACON, cypa_to: MIP, docs_fee: 1000.00, online_booking_fee: 0.00, stack_run: 0.00, expenses: 175.00, total_expenses: 1175.00`
  - `container_size: 40ft, cypa_from: SEACON, cypa_to: MIP, docs_fee: 1000.00, online_booking_fee: 0.00, stack_run: 0.00, expenses: 300.00, total_expenses: 1300.00`
  - `container_size: 20ft, cypa_from: OCEANBOX, cypa_to: MIP, docs_fee: 700.00, online_booking_fee: 0.00, stack_run: 0.00, expenses: 135.00, total_expenses: 835.00`
  - `container_size: 40ft, cypa_from: OCEANBOX, cypa_to: MIP, docs_fee: 700.00, online_booking_fee: 0.00, stack_run: 0.00, expenses: 220.00, total_expenses: 920.00`
  - `container_size: 40ft, cypa_from: IRS, cypa_to: CAVITE, docs_fee: 800.00, online_booking_fee: 0.00, stack_run: 0.00, expenses: 650.00, total_expenses: 1450.00`
  - `container_size: 40ft, cypa_from: MIP, cypa_to: TOCSI, docs_fee: 500.00, online_booking_fee: 0.00, stack_run: 0.00, expenses: 490.00, total_expenses: 990.00`

## 4) RO ILAGAN (`shipping_lines.id = 4`)

- **shippingline info**
  - name: `RO ILAGAN`
  - short_name: `RO ILAGAN`
- **transaction_information_template**
  - IDs: `[11, 13, 14, 15, 16, 17, 18, 19, 24]`
  - names: `[Date, Origin, Destination, Waybill, Remarks, Plate Number, Container Number, Size, Amount]`
- **rate per client**
  - none
- **fixed expense []**
  - none

## 5) INTERASIA (`shipping_lines.id = 5`)

- **shippingline info**
  - name: `FREIGHT CONNECTION PHIL INC`
  - short_name: `INTERASIA`
- **transaction_information_template**
  - IDs: `[11, 13, 14, 15, 16, 17, 18, 19, 24]`
  - names: `[Date, Origin, Destination, Waybill, Remarks, Plate Number, Container Number, Size, Amount]`
- **rate per client**
  - `CY: ALL CY, container_size: 20ft, rate: 7500, stack_run: 0, tax_percent: 12, no_of_days: 15`
  - `CY: ALL CY, container_size: 40ft, rate: 7500, stack_run: 0, tax_percent: 12, no_of_days: 15`
- **fixed expense []**
  - none

## 6) MSC (`shipping_lines.id = 6`)

- **shippingline info**
  - name: `MEDITERRANEAN SHIPPING COMPANY PHILIPPINES`
  - short_name: `MSC`
- **transaction_information_template**
  - IDs: `[11, 13, 14, 15, 16, 17, 18, 19, 23, 24, 25]`
  - names: `[Date, Origin, Destination, Waybill, Remarks, Plate Number, Container Number, Size, 12% VAT, Amount, Total Amount]`
- **rate per client**
  - `CY: ALL CY, container_size: 20ft, rate: 6500, stack_run: 0, tax_percent: 12, no_of_days: 30`
  - `CY: ALL CY, container_size: 40ft, rate: 6500, stack_run: 0, tax_percent: 12, no_of_days: 30`
- **fixed expense []**
  - `container_size: 20ft, cypa_from: SEACON, cypa_to: MIP, docs_fee: 600.00, online_booking_fee: 0.00, stack_run: 0.00, expenses: 175.00, total_expenses: 775.00`
  - `container_size: 40ft, cypa_from: SEACON, cypa_to: MIP, docs_fee: 600.00, online_booking_fee: 0.00, stack_run: 0.00, expenses: 300.00, total_expenses: 900.00`
  - `container_size: 20ft, cypa_from: IRS, cypa_to: MIP, docs_fee: 800.00, online_booking_fee: 0.00, stack_run: 0.00, expenses: 160.00, total_expenses: 960.00`
  - `container_size: 40ft, cypa_from: IRS, cypa_to: MIP, docs_fee: 800.00, online_booking_fee: 0.00, stack_run: 0.00, expenses: 270.00, total_expenses: 1070.00`

## 7) MEDLOG (`shipping_lines.id = 7`)

- **shippingline info**
  - name: `MEDLOG PHILIPPINES INC`
  - short_name: `MEDLOG`
- **transaction_information_template**
  - IDs: `[11, 13, 14, 15, 16, 17, 18, 19, 23, 24, 25]`
  - names: `[Date, Origin, Destination, Waybill, Remarks, Plate Number, Container Number, Size, 12% VAT, Amount, Total Amount]`
- **rate per client**
  - `CY: ALL CY, container_size: 20ft, rate: 5000, stack_run: 0, tax_percent: 12, no_of_days: 30`
  - `CY: ALL CY, container_size: 40ft, rate: 5000, stack_run: 0, tax_percent: 12, no_of_days: 30`
- **fixed expense []**
  - `container_size: 20ft, cypa_from: MEDLOG, cypa_to: MIP, docs_fee: 0.00, online_booking_fee: 0.00, stack_run: 0.00, expenses: 75.00, total_expenses: 75.00`
  - `container_size: 40ft, cypa_from: MEDLOG, cypa_to: MIP, docs_fee: 0.00, online_booking_fee: 0.00, stack_run: 0.00, expenses: 100.00, total_expenses: 100.00`

## 8) NCT (`shipping_lines.id = 8`)

- **shippingline info**
  - name: `NCT TRANS NATIONAL CORP`
  - short_name: `NCT`
- **transaction_information_template**
  - IDs: `[11, 12, 13, 14, 15, 16, 17, 18, 19, 20, 21, 22, 23, 24, 25]`
  - names: `[Date, Booking Number, Origin, Destination, Waybill, Remarks, Plate Number, Container Number, Size, Vessel, Work Order, Stack Run, 12% VAT, Amount, Total Amount]`
- **rate per client**
  - none
- **fixed expense []**
  - `container_size: 20ft, cypa_from: NCT, cypa_to: MIP, docs_fee: 0.00, online_booking_fee: 0.00, stack_run: 0.00, expenses: 175.00, total_expenses: 175.00`
  - `container_size: 40ft, cypa_from: NCT, cypa_to: MIP, docs_fee: 0.00, online_booking_fee: 0.00, stack_run: 0.00, expenses: 300.00, total_expenses: 300.00`

## 9) TS LINE (`shipping_lines.id = 9`)

- **shippingline info**
  - name: `TS LINES LTD, C/O TSL CONTAINER LINES PHIL INC`
  - short_name: `TS LINE`
- **transaction_information_template**
  - IDs: `[11, 13, 14, 15, 16, 17, 18, 19, 24]`
  - names: `[Date, Origin, Destination, Waybill, Remarks, Plate Number, Container Number, Size, Amount]`
- **rate per client**
  - `CY: ALL CY, container_size: 20ft, rate: 6700, stack_run: 0, tax_percent: 12, no_of_days: 15`
  - `CY: ALL CY, container_size: 40ft, rate: 6700, stack_run: 0, tax_percent: 12, no_of_days: 15`
- **fixed expense []**
  - `container_size: 20ft, cypa_from: OCEANBOX, cypa_to: MIP, docs_fee: 700.00, online_booking_fee: 0.00, stack_run: 0.00, expenses: 135.00, total_expenses: 835.00`
  - `container_size: 40ft, cypa_from: OCEANBOX, cypa_to: MIP, docs_fee: 700.00, online_booking_fee: 0.00, stack_run: 0.00, expenses: 220.00, total_expenses: 920.00`

## 10) SEA LEAD (`shipping_lines.id = 10`)

- **shippingline info**
  - name: `SEALEAD SHIPPING PTE. LTD.`
  - short_name: `SEA LEAD`
- **transaction_information_template**
  - IDs: `[11, 13, 14, 15, 16, 17, 18, 19, 24]`
  - names: `[Date, Origin, Destination, Waybill, Remarks, Plate Number, Container Number, Size, Amount]`
- **rate per client**
  - `CY: ALL CY, container_size: 20ft, rate: 6500, stack_run: 0, tax_percent: 12, no_of_days: 15`
  - `CY: ALL CY, container_size: 40ft, rate: 6500, stack_run: 0, tax_percent: 12, no_of_days: 15`
- **fixed expense []**
  - none

## 11) HYUNDAI / HMM (`shipping_lines.id = 11`)

- **shippingline info**
  - name: `HMM (Philippines), Inc.`
  - short_name: `HYUNDAI`
- **transaction_information_template**
  - IDs: `[11, 13, 14, 15, 16, 17, 18, 19, 24]`
  - names: `[Date, Origin, Destination, Waybill, Remarks, Plate Number, Container Number, Size, Amount]`
- **rate per client**
  - `CY: OCEANBOX, container_size: 20ft, rate: 7300, stack_run: 0, tax_percent: 12, no_of_days: 30`
  - `CY: OCEANBOX, container_size: 40ft, rate: 7300, stack_run: 0, tax_percent: 12, no_of_days: 30`
- **fixed expense []**
  - `container_size: 20ft, cypa_from: OCEANBOX, cypa_to: MIP, docs_fee: 700.00, online_booking_fee: 0.00, stack_run: 0.00, expenses: 135.00, total_expenses: 835.00`
  - `container_size: 40ft, cypa_from: OCEANBOX, cypa_to: MIP, docs_fee: 700.00, online_booking_fee: 0.00, stack_run: 0.00, expenses: 220.00, total_expenses: 920.00`

## 12) CMA CGM (`shipping_lines.id = 12`)

- **shippingline info**
  - name: `CMA CGM`
  - short_name: `CMA CGM`
- **transaction_information_template**
  - IDs: `[11, 13, 14, 15, 16, 17, 18, 19, 24]`
  - names: `[Date, Origin, Destination, Waybill, Remarks, Plate Number, Container Number, Size, Amount]`
- **rate per client**
  - none
- **fixed expense []**
  - `container_size: 40ft, cypa_from: MNHPI, cypa_to: MILT, docs_fee: 850.00, online_booking_fee: 0.00, stack_run: 0.00, expenses: 700.00, total_expenses: 1550.00`

## 13) IAL (`shipping_lines.id = 13`)

- **shippingline info**
  - name: `IAL`
  - short_name: `IAL`
- **transaction_information_template**
  - IDs: `[11, 13, 14, 15, 16, 17, 18, 19, 24]`
  - names: `[Date, Origin, Destination, Waybill, Remarks, Plate Number, Container Number, Size, Amount]`
- **rate per client**
  - none
- **fixed expense []**
  - `container_size: 20ft, cypa_from: OCEANBOX, cypa_to: MIP, docs_fee: 700.00, online_booking_fee: 0.00, stack_run: 0.00, expenses: 135.00, total_expenses: 835.00`
  - `container_size: 40ft, cypa_from: OCEANBOX, cypa_to: MIP, docs_fee: 700.00, online_booking_fee: 0.00, stack_run: 0.00, expenses: 220.00, total_expenses: 920.00`

## 14) AMC (`shipping_lines.id = 14`)

- **shippingline info**
  - name: `AMC`
  - short_name: `AMC`
- **transaction_information_template**
  - IDs: `[11, 13, 14, 15, 16, 17, 18, 19, 24]`
  - names: `[Date, Origin, Destination, Waybill, Remarks, Plate Number, Container Number, Size, Amount]`
- **rate per client**
  - none
- **fixed expense []**
  - `container_size: 20ft, cypa_from: OCEANBOX, cypa_to: SOUTH, docs_fee: 700.00, online_booking_fee: 0.00, stack_run: 0.00, expenses: 135.00, total_expenses: 835.00`
  - `container_size: 40ft, cypa_from: OCEANBOX, cypa_to: SOUTH, docs_fee: 700.00, online_booking_fee: 0.00, stack_run: 0.00, expenses: 220.00, total_expenses: 920.00`

## 15) SINO TRANS (`shipping_lines.id = 15`)

- **shippingline info**
  - name: `SINO TRANS`
  - short_name: `SINO TRANS`
- **transaction_information_template**
  - IDs: `[11, 13, 14, 15, 16, 17, 18, 19, 24]`
  - names: `[Date, Origin, Destination, Waybill, Remarks, Plate Number, Container Number, Size, Amount]`
- **rate per client**
  - none
- **fixed expense []**
  - `container_size: 20ft, cypa_from: OCEANBOX, cypa_to: MIP, docs_fee: 700.00, online_booking_fee: 0.00, stack_run: 0.00, expenses: 135.00, total_expenses: 835.00`
  - `container_size: 40ft, cypa_from: OCEANBOX, cypa_to: MIP, docs_fee: 700.00, online_booking_fee: 0.00, stack_run: 0.00, expenses: 220.00, total_expenses: 920.00`

