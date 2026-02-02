# SOA Data Options / Templates Documentation

## Shipping Line Information Template

| Field Name | Data Field | Table |
|------------|------------|-------|
| Name | name | `shipping_lines` |
| Email Address | email_address | `shipping_lines` |
| Address | address | `shipping_lines` |
| Contact Name | contact_name | `shipping_lines` |
| Contact Mobile | contact_mobile | `shipping_lines` |
| Landlines | landlines | `shipping_lines` |
| Fax No | fax_no | `shipping_lines` |
| TIN | tin | `shipping_lines` |

## Transaction Information Template

| Field Name | Data Field | Table | Notes |
|------------|------------|-------|-------|
| Date | transaction_date | `waybill_details` | Format: d-M |
| Booking Number | reference_number | `bookings` | Via booking relationship |
| Origin | name | `cypa_details` | Via booking->cypaFrom (also supports "From" alias) |
| Destination | name | `cypa_details` | Via booking->cypaTo (also supports "To" alias) |
| Waybill | waybill_number | `waybill_details` | |
| Remarks | remarks | `rate_per_clients` | Via ratePerClient relationship |
| Plate Number | plate_number | `fleet_trucks` | Via relationship |
| Container Number | container_number | `containers` | Via booking relationship |
| Size | container_size | `waybill_details` | Format: 1X40HC or 1X20FR |
| Vessel | N/A | N/A | Not implemented (returns "-") |
| Work Order | N/A | N/A | Not implemented (returns "-") |
| Stack Run | stack_run | `rate_per_clients` | Via ratePerClient relationship |
| VAT | Calculated | Calculated | 12% of Amount |
| Amount | total_rate_per_client / rate | `waybill_details` / `rate_per_clients` | Falls back if 0 |
| Total Amount | Calculated | Calculated | Amount + VAT |

## Special Cases

### Amount Field
1. Primary: `waybill_details.total_rate_per_client`
2. Fallback: `rate_per_clients.rate` (via relationship)
3. Fallback: Matching `rate_per_clients` record (by shipping_line_id, container_size, cypa_id)

### Helper Field
- Reads `waybill_details.helper_id` (JSON array)
- Queries `helpers` table for matching IDs
- Returns comma-separated names

### Container Number
- From `containers` table
- Matched by `waybill_id` via booking relationship
