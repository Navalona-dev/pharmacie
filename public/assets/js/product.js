
function updateLigneProduct(elt, idProduit, idAffaire) {
    var anchorName = document.location.hash.substring(1);

    $(".loadBody").css('display', 'block');
    //$("#financiereProductTab tbody").sortable("destroy");
    $.ajax({
        type: 'post',
        url: '/admin/vente/edit_produit',
        data: {idAffaire: idAffaire, type: 'affaire', idProduit: idProduit} ,
        success: function (response) {
            $("#tr_vente_produit_"+idProduit).replaceWith(response);

            $(".loadBody").css('display', 'none');

            if (anchorName) {
                window.location.hash = anchorName;
            }

            return false;
        }
    });

    return false;
}

function editLigneProduct(elt, idAffaire, idProduit, position = null, typeVente = null, volumeGros = null, volumeDetail = null) {
    var anchorName = document.location.hash.substring(1);

    $("#qtt").css('border', '1px solid #e5e6e7');
    
    var qtt = $("#qtt").val();
    var qttRestant = $("#qttRestant").val();
    if (qtt === "" || qtt < 0) {
        $("#qtt").css('border', '1px solid red');
        return false;
    }

    if (qttRestant != undefined && qttRestant != "") {
       
        var qttTotal =  1

        if (typeVente == 'gros') {
            qttTotal =  parseFloat(qttRestant);
        } 
        if (typeVente == 'detail' && volumeGros > 0) {
            qttTotal =  parseFloat(volumeGros);
        } 
        if (typeVente == 'detail' && volumeGros < 1) {
            qttTotal =  parseFloat(qttRestant);
        }

        //return false;

        var message = '';
        if(typeVente == 'detail' && parseFloat(qtt) >= parseFloat(qttTotal)) {
            message = 'Votre qtt ne doit pas dépasser le volume de gros !!';
        } else if(typeVente == 'gros' && parseFloat(qtt) > parseFloat(qttTotal)) {
            message = 'Votre qtt ne doit pas dépasser le stock !!';
        }
        
            if ((typeVente == 'detail' && parseFloat(qtt) >= parseFloat(qttTotal)) || (typeVente == 'gros' && parseFloat(qtt) > parseFloat(qttTotal))) {

                // Alerte si la condition est remplie
                $(elt).parent('td').parent('tr').css('background-color', '#fc8b8b');
                setTimeout(function () {
                    toastr.options = {
                        closeButton: true,
                        progressBar: true,
                        showMethod: 'slideDown',
                        timeOut: 3000
                    };

                    toastr.error(message);

                }, 800);
                return false;
            }
    }
   
    $(".loadBody").css('display', 'block');
    $.ajax({
        type: 'post',
        url: '/admin/vente/save/edit_produit',
        data: {idAffaire: idAffaire, idProduit: idProduit, qtt: qtt},
        success: function (response) {
            //reloadTabFinanciere(response);
           /* $("#venteProduct").empty();
            $("#venteProduct").replaceWith(response);*/
            updateTable(idAffaire);
            //updatePositionBdd()
            $(".loadBody").css('display', 'none');

            if (anchorName) {
                window.location.hash = anchorName;
            }

        },
        error: function () {
            $(".loadBody").css('display', 'none');
            $(".chargementError").css('display', 'block');

        }
    });

    /*$("#venteProductTab tbody").sortable({
        helper: fixHelperModifiedTabFinanciere,
        stop: updateIndexFinanciere
    }).disableSelection();*/

    return false;
}

function deleteProduitAffaire(elt, idProduit, idAffaire) {
    var anchorName = document.location.hash.substring(1);

    if (confirm("Voulez vous vraiment supprimer ce produit")) {
        $(".loadBody").css("display", "block");
        $.ajax({
            type: 'post',
            url: '/admin/vente/delete-produit',
            data: {idProduit: idProduit, idAffaire: idAffaire },
            success: function (response) {
                //$(elt).parent('td').parent('tr').remove();

                /*$("#venteProduct").empty();
                $("#venteProduct").replaceWith(response);*/
                updateTable(idAffaire);

                $(".loadBody").css("display", "none");

                if (anchorName) {
                    window.location.hash = anchorName;
                }

            },
            error: function () {
                $(".loadBody").css('display', 'none');
                $(".chargementError").css('display', 'block');
                //alert("Error lors de la suppression");
            }
        });
    }


    return false;
}

function updateTable(id = null) {

    $.ajax({
        url: '/admin/vente/affaire/' + id + '/produits',
        type: 'GET',
        success: function (response) {
            let produitTableBody = '';
            const produits = response.produits;
            const montantHt = response.montantHt;

            if (produits.length > 0) {
                produits.forEach(function(produit) {
                    produitTableBody += `
                        <tr id="tr_vente_produit_${produit.id}" data-id="${produit.id}"
                                        data-id-affaire="${id}">
                            <td>${produit.reference}</td>
                            <td>${produit.nom}</td>
                            <td>${produit.qtt} ${produit.unite} </td>
                            <td>${produit.puHt} Ar / ${produit.unite} </td> 
                            <td>Pas de remise</td>
                            <td>${produit.total} Ar</td>
                            <td>
                                <a href="#" class="action-pencil d-block text-center mb-2" onclick="return updateLigneProduct(this, ${produit.id}, ${id})"><i class="bi bi-pencil"></i></a>
                                <a href="#" class="action-trash d-block text-center mb-2" onclick="deleteProduitAffaire(this, ${produit.id}, ${id})"><i class="bi bi-trash-fill"></i></a>
                            </td>
                        </tr>
                    `;
                });
            } else {
                produitTableBody += `
                    <tr>
                        <td colspan="7">Aucun produit trouvé</td>
                    </tr>
                `;
            }
            
            // Mettez à jour le corps du tableau
            $('.detail tbody').html(produitTableBody);

            console.log(affaire.id);

            if(montantHt > 0) {
                $('.montant-total').show();
                 // Mettez à jour l'affichage du montant HT
                $('.montant-ht').text(montantHt);

                let htmlContent = '';
                htmlContent += `<a href="#" onclick="return validerCommande(${id})" class="btn btn-primary btn-sm px-3">Valider</a>`;

                $('.btn-action').html(htmlContent);
            }

        },
        error: function (jqXHR, textStatus, errorThrown) {
            alert('Erreur lors de la récupération des produits : ' + textStatus);
        }
    });
}

