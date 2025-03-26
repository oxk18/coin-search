<?php
include_once('./_common.php');
if (!defined('_GNUBOARD_')) exit;

// API Functions
function get_crypto_price($coin_id = 'bitcoin') {
    $url = "https://api.coingecko.com/api/v3/simple/price?ids={$coin_id}&vs_currencies=usd,krw";
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        'Accept: application/json',
        'User-Agent: Mozilla/5.0'
    ));
    
    $response = curl_exec($ch);
    
    if(curl_errno($ch)) {
        error_log('Curl error in get_crypto_price: ' . curl_error($ch));
        return null;
    }
    
    curl_close($ch);
    
    if ($response === false || empty($response)) {
        error_log('Empty API response from CoinGecko price endpoint');
        return null;
    }
    
    $data = json_decode($response, true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        error_log('JSON decode error in price data: ' . json_last_error_msg());
        return null;
    }
    
    return $data;
}

function get_coin_history($coin_id) {
    $url = "https://api.coingecko.com/api/v3/coins/{$coin_id}/market_chart?vs_currency=usd&days=7&interval=daily";
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        'Accept: application/json',
        'User-Agent: Mozilla/5.0'
    ));
    
    $response = curl_exec($ch);
    curl_close($ch);
    
    if ($response === false || empty($response)) {
        return null;
    }
    
    return json_decode($response, true);
}

function get_available_coins() {
    $url = "https://api.coingecko.com/api/v3/coins/markets?vs_currency=usd&order=market_cap_desc&per_page=250&page=1";
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        'Accept: application/json',
        'User-Agent: Mozilla/5.0' // Add User-Agent header
    ));
    
    $response = curl_exec($ch);
    
    if(curl_errno($ch)) {
        error_log('Curl error: ' . curl_error($ch));
        return array();
    }
    
    curl_close($ch);
    
    // Debug the response
    error_log('API Response: ' . $response);
    
    if ($response === false || empty($response)) {
        error_log('Empty API response from CoinGecko');
        return array();
    }
    
    $data = json_decode($response, true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        error_log('JSON decode error: ' . json_last_error_msg());
        return array();
    }
    
    return $data;
}


// Get available coins (move this after head.php)
$available_coins = get_available_coins();

// Debug output (move this after $available_coins is defined)
error_log('Available coins: ' . print_r($available_coins, true));

if (empty($available_coins)) {
    echo '<div class="alert alert-danger">코인 목록을 불러오는데 실패했습니다. 잠시 후 다시 시도해주세요.</div>';
}

// Page Display
$g5['title'] = "암호화폐 검색";
include_once(G5_PATH.'/head.php');


?>

<div class="coin-search-container">
<h2 class="main-title">암호화폐 검색</h2>    
    <form method="get" action="<?php echo $_SERVER['PHP_SELF']; ?>">
        <div class="form-group">
        <div class="select-wrapper">
        <select name="sort_by" class="sort-select" onchange="this.form.submit()">
                <option value="market_cap_desc" <?php echo (isset($_GET['sort_by']) && $_GET['sort_by'] == 'market_cap_desc') ? 'selected' : ''; ?>>시가총액 (높은순)</option>
                <option value="market_cap_asc" <?php echo (isset($_GET['sort_by']) && $_GET['sort_by'] == 'market_cap_asc') ? 'selected' : ''; ?>>시가총액 (낮은순)</option>
                <option value="name_asc" <?php echo (isset($_GET['sort_by']) && $_GET['sort_by'] == 'name_asc') ? 'selected' : ''; ?>>이름 (가나다순)</option>
                <option value="name_desc" <?php echo (isset($_GET['sort_by']) && $_GET['sort_by'] == 'name_desc') ? 'selected' : ''; ?>>이름 (역순)</option>
                <option value="price_desc" <?php echo (isset($_GET['sort_by']) && $_GET['sort_by'] == 'price_desc') ? 'selected' : ''; ?>>가격 (높은순)</option>
                <option value="price_asc" <?php echo (isset($_GET['sort_by']) && $_GET['sort_by'] == 'price_asc') ? 'selected' : ''; ?>>가격 (낮은순)</option>
                <option value="change_desc" <?php echo (isset($_GET['sort_by']) && $_GET['sort_by'] == 'change_desc') ? 'selected' : ''; ?>>변동률 (높은순)</option>
                <option value="change_asc" <?php echo (isset($_GET['sort_by']) && $_GET['sort_by'] == 'change_asc') ? 'selected' : ''; ?>>변동률 (낮은순)</option>
            </select>
            </div>
            <div class="select-wrapper">
            <select name="coin_id" class="coin-select">
                <option value="">코인을 선택하세요</option>
                <?php 
                // Sort the coins based on selected option
                if (!empty($available_coins) && is_array($available_coins)) {
                if (isset($_GET['sort_by'])) {
                    switch ($_GET['sort_by']) {
                        case 'name_asc':
                            usort($available_coins, function($a, $b) {
                                return strcmp($a['name'], $b['name']);
                            });
                            break;
                        case 'name_desc':
                            usort($available_coins, function($a, $b) {
                                return strcmp($b['name'], $a['name']);
                            });
                            break;
                        case 'market_cap_asc':
                            usort($available_coins, function($a, $b) {
                                return $a['market_cap'] - $b['market_cap'];
                            });
                            break;
                        // ... rest of the sorting options ...
                    }
                }
                foreach ($available_coins as $coin): 
                    if (isset($coin['id']) && isset($coin['name']) && isset($coin['symbol'])):
            ?>
                    <option value="<?php echo htmlspecialchars($coin['id']); ?>" 
                        <?php echo (isset($_GET['coin_id']) && $_GET['coin_id'] == $coin['id']) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($coin['name']) . ' (' . strtoupper(htmlspecialchars($coin['symbol'])) . ')'; ?>
                    </option>
            <?php 
                    endif;
                endforeach;
            } else {
                echo '<option value="" disabled>코인 데이터를 불러올 수 없습니다</option>';
            }
            ?>
            </select>
            </div>
            <button type="submit" class="btn_submit">검색</button>
        </div>
    </form>

    <?php if (isset($_GET['coin_id']) && !empty($_GET['coin_id'])) { 
        $price_data = get_crypto_price($_GET['coin_id']);
        $selected_coin = array_filter($available_coins, function($coin) {
            return $coin['id'] == $_GET['coin_id'];
        });
        $selected_coin = reset($selected_coin);
        
        if ($selected_coin && $price_data && isset($price_data[$_GET['coin_id']])) {
            echo '<div class="coin-results">';
            echo '<div class="coin-item">';
            echo '<div class="coin-header">';
            echo '<img src="'.htmlspecialchars($selected_coin['image']).'" alt="'.htmlspecialchars($selected_coin['name']).'">';
            echo '<h3>'.htmlspecialchars($selected_coin['name']).' ('.strtoupper(htmlspecialchars($selected_coin['symbol'])).')</h3>';
            echo '</div>';
            echo '<div class="coin-details">';
            // Format USD price
           $usd_price = $price_data[$_GET['coin_id']]['usd'];
            if ($usd_price < 0.01) {
            $decimal_places = strlen(rtrim(substr(strrchr(sprintf("%.20f", $usd_price), "."), 1), '0'));
            $formatted_usd = sprintf("$%.{$decimal_places}f", $usd_price);
            } else {
            $formatted_usd = '$'.number_format($usd_price, 2);
            }
    
            // Format KRW price
            $krw_price = $price_data[$_GET['coin_id']]['krw'];
            if ($krw_price < 1) {
            $decimal_places = strlen(rtrim(substr(strrchr(sprintf("%.20f", $krw_price), "."), 1), '0'));
            $formatted_krw = sprintf("₩%.{$decimal_places}f", $krw_price);
            } else {
            $formatted_krw = '₩'.number_format($krw_price, 0);
            }
    
            echo '<div class="price-info">';
            echo '<p><span>USD:</span> '.$formatted_usd.'</p>';
            echo '<p><span>KRW:</span> '.$formatted_krw.'</p>';
            echo '</div>';
            echo '<div class="market-info">';
            echo '<p><span>24h 변동:</span> <span class="'.($selected_coin['price_change_percentage_24h'] >= 0 ? 'positive' : 'negative').'">'.number_format($selected_coin['price_change_percentage_24h'], 2).'%</span></p>';
            echo '<p><span>시가총액:</span> $'.number_format($selected_coin['market_cap'], 0).'</p>';
            echo '</div>';
            echo '</div>';
            echo '<div class="chart-container">';
            echo '<canvas id="priceChart"></canvas>';
            echo '</div>';
            echo '</div>';

            // Get historical data
            $history_data = get_coin_history($_GET['coin_id']);
            if ($history_data && isset($history_data['prices'])) {
            $chart_data = array_map(function($item) {
            return [
            'date' => date('Y-m-d', $item[0] / 1000),
            'price' => $item[1]
            ];
            }, $history_data['prices']);
    
            echo '<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>';
            echo '<script>
            const ctx = document.getElementById("priceChart");
            new Chart(ctx, {
            type: "line",
            data: {
            labels: '.json_encode(array_column($chart_data, "date")).',
            datasets: [{
            label: "Price (USD)",
            data: '.json_encode(array_column($chart_data, "price")).',
            borderColor: "#4a90e2",
            tension: 0.1,
            fill: false
            }]
            },
            options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
            title: {
                display: true,
                text: "7-Day Price History"
            }
            },
            scales: {
            y: {
                beginAtZero: false,
                ticks: {
                    font: {
                        size: 12
                    }
                }
            },
            x: {
                ticks: {
                    font: {
                        size: 12
                                 }
                            }
                        }
                    }
                }
            });
    </script>';
        }
        echo '</div>';
        }  else {
            echo '<div class="alert alert-danger">코인 정보를 불러올 수 없습니다. 잠시 후 다시 시도해주세요.</div>';
        }
    }
    ?>    
    
</div>

<style>
.coin-search-container {
    max-width: 800px;
    margin: 20px auto;
    padding: 20px;
    background: #ffffff;
    border-radius: 12px;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
}

.main-title {
    color: #333;
    text-align: center;
    margin-bottom: 30px;
    font-size: 28px;
}

.form-group {
    display: flex;
    flex-wrap: wrap;
    gap: 15px;
    margin-bottom: 30px;
}

.select-wrapper {
    flex: 1;
    min-width: 200px;
    position: relative;
}

.sort-select,
.coin-select {
    width: 100%;
    padding: 12px;
    border: 2px solid #e1e1e1;
    border-radius: 8px;
    background: #f8f9fa;
    font-size: 16px;
    transition: all 0.3s ease;
}

.sort-select:focus,
.coin-select:focus {
    border-color: #4a90e2;
    outline: none;
    box-shadow: 0 0 0 3px rgba(74, 144, 226, 0.1);
}

.btn_submit {
    padding: 12px 30px;
    background: #4a90e2;
    color: white;
    border: none;
    border-radius: 8px;
    font-size: 16px;
    cursor: pointer;
    transition: background 0.3s ease;
}

.btn_submit:hover {
    background: #357abd;
}

.coin-results {
    margin-top: 30px;
}

.coin-item {
    background: #ffffff;
    border-radius: 12px;
    padding: 25px;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
    transition: transform 0.3s ease;
}

.coin-item:hover {
    transform: translateY(-5px);
}

.coin-header {
    display: flex;
    align-items: center;
    gap: 15px;
    margin-bottom: 20px;
}

.coin-header img {
    width: 48px;
    height: 48px;
}

.coin-header h3 {
    margin: 0;
    color: #333;
    font-size: 24px;
}

.coin-details {
    display: grid;
    gap: 20px;
}

.price-info,
.market-info {
    display: grid;
    gap: 10px;
}

.price-info p,
.market-info p {
    display: flex;
    justify-content: space-between;
    margin: 0;
    padding: 10px;
    background: #f8f9fa;
    border-radius: 6px;
    font-size: 18px;
}

.positive {
    color: #28a745;
}

.negative {
    color: #dc3545;
}

.alert {
    padding: 15px;
    border-radius: 8px;
    margin: 20px 0;
    text-align: center;
}

.alert-danger {
    background: #fff3f3;
    color: #dc3545;
    border: 1px solid #ffcdd2;
}

.chart-container {
    margin-top: 20px;
    padding: 15px;
    background: #fff;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.05);
    position: relative;
    height: 300px;  /* 고정 높이 추가 */
    width: 100%
}

@media (max-width: 768px) {
    .coin-search-container {
        margin: 10px;
        padding: 15px;
    }

    .form-group {
        flex-direction: column;
    }

    .select-wrapper {
        width: 100%;
    }

    .btn_submit {
        width: 100%;
    }

    .coin-header {
        flex-direction: column;
        text-align: center;
    }

    .coin-header h3 {
        font-size: 20px;
    }

    .price-info p,
    .market-info p {
        font-size: 16px;
    }

    .chart-container {
        height: 250px;  /* 모바일에서의 높이 조정 */
        padding: 10px;
        margin: 10px 0;
    }
}
</style>

<?php
include_once(G5_PATH.'/tail.php');
?>