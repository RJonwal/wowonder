<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Subscription Payment Failed</title>

    <style>
    .commanTable {
        display: flex;
        justify-content: center;
        margin-top: 100px;
    }
    .commanTable th .header {
        background-color: #ff5733;
        color: #fff;
        text-align: center;
        font-size: 13px;
        display: inline-block;
        padding: 20px 30px;
        font-family: 'roboto', sans-serif;
        position: relative;
        top: -35px;
    }

    .commanTable th .header h1{
        font-size: 24px;
    }

    .commanTable table  tr:first-child td h1{
        margin: 0px;        
    }
    .commanTable table  tr td p{
        margin: 20px 0px;        
    }
    .commanTable table  tr td ul {
        display: flex;
        flex-direction: column;
        gap: 10px;
        margin: 30px 0;
    }
    .targetBtn{
        background-color: #ff5733;
        color: #fff;
        border: none;
        box-shadow: none;
        font-size: 17px;
        font-weight: 700;
        -webkit-border-radius: 4px;
        text-decoration: none;
        border-radius: 5px;
        display: inline-flex;
        padding: 10px 32px;
        cursor: pointer;
        transition: all ease-in-out 0.3s;
        margin-top: 20px;
    }
    .targetBtn:hover{
        opacity: 0.9;
    }

    p {
        font-family: 'Roboto', sans-serif;
        font-size: 14px;
    }

    li {
        font-family: 'roboto', sans-serif;
        font-size: 14px;
        line-height: auto;
    }
    .common-header{
        text-align: center;
    }

    .subtable td div{
        font-size: 14px;
    }

    .header{
        position: relative;
    }

    .header::before{
        content: '';
        position: absolute;
        left: 0;
        bottom: 0;
        width: 100%;
        height: 200px;
        background-color: whitesmoke;
    }
</style>
</head>
<body>
    <div class="commanTable" style="color:black; margin:0;">
        <table width="600" cellspacing="0" cellpadding="0" style="background-color: whitesmoke;">
            <thead>
                <tr class="common-header"  style="margin-top: -35px;">
                    <th>
                        <div class="header" style="margin: 0 auto; margin-top: -35px; align-items: center;  display: flex; padding: 0px 20px">
                            <h1>Subscription Payment Failed</h1>
                        </div>
                    </th>
                </th>
            </thead>
            <tbody style="background-color: whitesmoke;">
            <tr>
                <td style="padding:20px 20px;">
                    <p>Dear [CustomerName],</p>
                    <p>We regret to inform you that the payment for your subscription with has failed.</p>
                    <p>Here are some details about the failed payment:</p>
                    <table class="subtable">
                        <tbody>
                            <tr>
                                <td>
                                    <div><strong>Subscription Plan:</strong> [PlanName]</div>
                                </td>
                            </tr>
                            <tr>
                                <td>
                                    <div><strong>Amount Due:</strong> <?php echo Wo_GetCurrency($wo['config']['currency']);?>[AmountCharged]</div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                    <p>To resolve this issue and avoid any service interruption, please update your payment information by clicking the link below:</p>
                    <p><a href="[InvoiceLink]" target="_blank" style="color: #fff;" class="targetBtn">Update Payment</a></p>
                </td>
            </tr>
            </tbody>
        </table>
    </div>
</body>
</html>